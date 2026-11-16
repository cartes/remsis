<?php

namespace Modules\Payroll\Services;

use Carbon\CarbonInterface;
use Modules\Companies\Models\Company;
use Modules\Core\Models\MonthlyIndicator;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmploymentContract;
use Modules\Payroll\Models\EmployeeItem;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;

/**
 * Haberes imponibles del mes de un colaborador: días pagados, sueldo proporcional,
 * horas extra, variables, ítems imponibles y gratificación.
 */
final class MonthlyPayrollEarnings
{
    /** Causales de ausencia que no se pagan. */
    private const UNPAID_JUSTIFICATIONS = [
        'permiso_sin_goce',
        'unpaid_leave',
        'accidente_trabajo',
        'work_accident',
        'suspension_disciplinaria',
        'suspension',
        'inasistencia_injustificada',
        'unjustified',
    ];

    /** Sueldo base pactado que regía en el período (versión del contrato vigente al cierre del mes). */
    public readonly int $baseSalary;

    /** Tipo de contrato que regía en el período. */
    public readonly string $contractType;

    public readonly int $workedDays;

    public readonly int $salary;

    public readonly float $overtimeHours;

    public readonly int $overtimeAmount;

    public readonly int $gratification;

    public readonly int $taxable;

    public readonly int $variables;

    public readonly int $taxableItems;

    /** @var list<array{assignment: EmployeeItem, amount: int}> */
    public readonly array $taxableItemLines;

    /** Objetos con ->value para resolver ítems expresados en UF o UTM. */
    public readonly object $uf;

    public readonly object $utm;

    public function __construct(Employee $employee, PayrollPeriod $period, ?Payroll $line, MonthlyIndicator $indicator)
    {
        $company = $period->company;
        $contract = $this->latestContract($employee);

        // Condiciones vigentes en el mes liquidado, no las actuales de la ficha: un anexo posterior
        // no altera la liquidación de un mes anterior. Sin historial aplicable se usa la ficha.
        $terms = EmploymentContract::inForceFor($employee->id, $period->end_date);
        $this->baseSalary = $terms && (float) $terms->base_salary > 0
            ? (int) round((float) $terms->base_salary)
            : (int) round((float) $employee->salary);
        $this->contractType = ($terms?->contract_type ?: $employee->contract_type) ?: EmploymentContract::TYPE_INDEFINITE;

        $this->uf = (object) ['value' => (float) $indicator->uf_value];
        $this->utm = (object) ['value' => (float) $indicator->utm_value];

        $this->workedDays = $this->paidDays($employee, $period, $contract);
        $this->salary = (int) round($this->baseSalary * $this->workedDays / 30);

        $this->overtimeHours = $company->allows_overtime ? (float) ($line?->overtime_hours ?? 0) : 0;
        $this->overtimeAmount = (int) round(
            $this->baseSalary * $this->overtimeFactor($employee, $company, $contract, $indicator) * $this->overtimeHours
        );
        $this->variables = $this->overtimeAmount + (int) $line?->comisiones_amount
            + (int) $line?->semana_corrida_amount + (int) $line?->bonos_imponibles_amount + (int) $line?->aguinaldos_amount;

        $totalItems = 0;
        $lines = [];
        $base = $this->salary + $this->variables;
        foreach ($employee->employeeItems as $assignment) {
            if (! $assignment->is_active || $assignment->item?->type !== 'haber_imponible') {
                continue;
            }
            $amount = $assignment->resolvedAmountCLP($this->uf, $this->utm, $this->salary, $this->workedDays);
            $totalItems += $amount;
            $lines[] = ['assignment' => $assignment, 'amount' => $amount];
            if ($assignment->item->is_gratification_base) {
                $base += $amount;
            }
        }

        $this->taxableItems = $totalItems;
        $this->taxableItemLines = $lines;
        $this->gratification = match ($company->gratification_system) {
            Company::GRATIFICATION_SYSTEM_ART_50 => (int) round(min($base * 0.25, $indicator->getGratificationCapClp())),
            Company::GRATIFICATION_SYSTEM_NONE => 0,
            default => (int) $line?->gratification_amount,
        };
        $this->taxable = $this->salary + $this->variables + $this->taxableItems + $this->gratification;
    }

    private function latestContract(Employee $employee): ?EmploymentContract
    {
        return EmploymentContract::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Factor de la hora extraordinaria con recargo del 50% (Art. 32 CT) sobre el sueldo mensual:
     * (7 / 30 / jornada semanal) * 1,5. Con 44 h es 0,0079545; con 42 h, 0,0083333.
     */
    private function overtimeFactor(Employee $employee, Company $company, ?EmploymentContract $contract, MonthlyIndicator $indicator): float
    {
        if ($contract?->work_schedule_type === EmploymentContract::SCHEDULE_ART_22) {
            return 0.0;
        }

        $weeklyHours = (float) ($employee->work_schedule_type === EmploymentContract::SCHEDULE_PART_TIME && $employee->part_time_hours > 0
            ? $employee->part_time_hours
            : ($company->weekly_hours ?: $indicator->legal_weekly_hours));

        return $weeklyHours > 0 ? (7 / 30 / $weeklyHours) * 1.5 : 0.0;
    }

    /**
     * Días pagados sobre base de 30: descuenta los días sin vínculo laboral
     * (ingreso o término dentro del período) y las ausencias no remuneradas.
     */
    private function paidDays(Employee $employee, PayrollPeriod $period, ?EmploymentContract $contract): int
    {
        $start = $period->start_date->copy()->startOfDay();
        $end = $period->end_date->copy()->startOfDay();

        $from = $employee->hire_date && $employee->hire_date->gt($start) ? $employee->hire_date->copy()->startOfDay() : $start;
        $termination = $contract?->termination_date;
        $to = $termination && $termination->lt($end) ? $termination->copy()->startOfDay() : $end;

        if ($from->gt($to)) {
            return 0;
        }

        $employedDays = match (true) {
            $from->eq($start) && $to->eq($end) => 30,
            $to->eq($end) => max(1, 30 - $this->daysBetween($start, $from)),
            default => min(30, $this->daysBetween($from, $to) + 1),
        };

        $absences = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', '>=', $from->toDateString())
            ->where('date', '<', $to->copy()->addDay()->toDateString())
            ->where(function ($query) {
                $query->where('status', 'licencia')
                    ->orWhere(function ($q) {
                        $q->where('status', 'ausente')
                            ->where(function ($q2) {
                                $q2->where('is_justified', false)
                                    ->orWhereIn('justification_type', self::UNPAID_JUSTIFICATIONS);
                            });
                    });
            })
            ->count();

        // Ausente todos los días calendario con vínculo vigente: no hay días pagados,
        // aunque el mes tenga 28 o 31 días.
        if ($absences >= $this->daysBetween($from, $to) + 1) {
            return 0;
        }

        return max(0, $employedDays - $absences);
    }

    private function daysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) round($from->diffInDays($to));
    }
}
