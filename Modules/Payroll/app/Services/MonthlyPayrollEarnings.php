<?php

namespace Modules\Payroll\Services;

use Modules\AdminPanel\Models\LegalParameter;
use Modules\Companies\Models\Company;
use Modules\Core\Models\MonthlyIndicator;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;

final class MonthlyPayrollEarnings
{
    public readonly int $workedDays;

    public readonly int $salary;

    public readonly float $overtimeHours;

    public readonly int $overtimeAmount;

    public readonly int $gratification;

    public readonly int $taxable;

    public readonly int $variables;

    public readonly int $taxableItems;

    public function __construct(Employee $employee, PayrollPeriod $period, ?Payroll $line, object $uf, object $utm)
    {
        $company = $period->company;
        $this->workedDays = $this->paidDays($employee, $period);
        $this->salary = (int) round($employee->salary * $this->workedDays / 30);
        $this->overtimeHours = $company->allows_overtime ? (float) ($line?->overtime_hours ?? 0) : 0;
        $factor = $company->weekly_hours == 44 ? 0.0079545 : 0.0077777;
        $this->overtimeAmount = (int) round($employee->salary * $factor * $this->overtimeHours);
        $this->variables = $this->overtimeAmount + (int) $line?->comisiones_amount
            + (int) $line?->semana_corrida_amount + (int) $line?->bonos_imponibles_amount + (int) $line?->aguinaldos_amount;

        $totalItems = 0;
        $base = $this->salary + $this->variables;
        foreach ($employee->employeeItems as $assignment) {
            if (! $assignment->is_active || $assignment->item?->type !== 'haber_imponible') {
                continue;
            }
            $amount = $assignment->resolvedAmountCLP($uf, $utm, $this->salary, $this->workedDays);
            $totalItems += $amount;
            if ($assignment->item->is_gratification_base) {
                $base += $amount;
            }
        }

        $this->taxableItems = $totalItems;
        $this->gratification = match ($company->gratification_system) {
            Company::GRATIFICATION_SYSTEM_ART_50 => $this->article50($period, $base),
            Company::GRATIFICATION_SYSTEM_NONE => 0,
            default => (int) $line?->gratification_amount,
        };
        $this->taxable = $this->salary + $this->variables + $this->taxableItems + $this->gratification;
    }

    private function paidDays(Employee $employee, PayrollPeriod $period): int
    {
        $discountableAbsencesCount = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', '>=', $period->start_date->toDateString())
            ->where('date', '<', $period->end_date->copy()->addDay()->toDateString())
            ->where(function ($query) {
                $query->where('status', 'licencia')
                    ->orWhere(function ($q) {
                        $q->where('status', 'ausente')
                            ->where(function ($q2) {
                                $q2->where('is_justified', false)
                                    ->orWhereIn('justification_type', [
                                        'permiso_sin_goce',
                                        'unpaid_leave',
                                        'accidente_trabajo',
                                        'work_accident',
                                        'suspension_disciplinaria',
                                        'suspension',
                                        'inasistencia_injustificada',
                                        'unjustified',
                                    ]);
                            });
                    });
            })
            ->count();

        // A complete calendar month on leave earns no salary, including short Februaries.
        if ($period->start_date->day === 1 && $period->end_date->isSameMonth($period->start_date)
            && $period->end_date->isLastOfMonth() && $discountableAbsencesCount >= $period->end_date->day) {
            return 0;
        }

        return max(0, 30 - $discountableAbsencesCount);
    }

    private function article50(PayrollPeriod $period, int $base): int
    {
        $indicator = MonthlyIndicator::where('year', $period->year)->where('month', $period->month)->first();
        if ($indicator?->imm_value > 0) {
            $cap = $indicator->getGratificationCapClp();
        } else {
            $imm = LegalParameter::where('key', 'monthly_minimum_wage')->value('value');
            $cap = 4.75 * ($imm > 0 ? $imm : 500000) / 12;
        }

        return (int) round(min($base * 0.25, $cap));
    }
}
