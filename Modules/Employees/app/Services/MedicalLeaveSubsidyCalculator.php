<?php

declare(strict_types=1);

namespace Modules\Employees\Services;

use Carbon\CarbonImmutable;
use Modules\Core\Models\MonthlyIndicator;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmploymentContract;
use Modules\Employees\Models\MedicalLeave;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Services\PayrollCalculationService;

/**
 * Base de cálculo del subsidio por incapacidad laboral (Art. 8 DFL 44 de 1978).
 *
 * Es el promedio de la remuneración mensual neta, del subsidio o de ambos, devengados en los
 * tres meses calendario anteriores al mes en que comienza la licencia. Remuneración neta es la
 * imponible menos las cotizaciones del trabajador y el impuesto único. El mes sin remuneración
 * ni subsidio se completa con la remuneración neta que resulta del contrato.
 *
 * El subsidio diario es la treintava parte de esa base y no puede ser inferior a la treintava
 * parte de la mitad del ingreso mínimo (Art. 17).
 *
 * Es una estimación para conciliar con la entidad pagadora, que es quien lo determina. No
 * separa las remuneraciones ocasionales (Art. 10) ni aplica el tope especial de los subsidios
 * maternales (Art. 8 inc. 2°).
 */
class MedicalLeaveSubsidyCalculator
{
    private const MONTHS = 3;

    private const HEALTH_RATE = 0.07;

    public function __construct(private readonly PayrollCalculationService $engine) {}

    /**
     * @return array{monthly: ?float, daily: ?float, detail: array<string, mixed>}
     */
    public function base(Employee $employee, CarbonImmutable $start, ?int $exceptLeaveId = null): array
    {
        $indicator = $this->indicatorAt($start);
        $months = [];
        $nets = [];

        for ($back = 1; $back <= self::MONTHS; $back++) {
            $month = $start->startOfMonth()->subMonths($back);
            $row = ['period' => $month->format('Y-m'), 'source' => null, 'net' => 0, 'subsidy' => 0];

            $payroll = Payroll::withoutGlobalScopes()
                ->where('employee_id', $employee->id)
                ->where('period_year', $month->year)
                ->where('period_month', $month->month)
                ->first();
            $net = $payroll ? $this->netOf($payroll) : 0;
            $subsidy = $this->subsidyAccruedIn($employee, $month, $start, $exceptLeaveId);

            if ($net + $subsidy > 0) {
                $row = ['source' => $net > 0 ? 'liquidacion' : 'subsidio', 'net' => $net, 'subsidy' => $subsidy] + $row;
            } elseif ($indicator && ($contractNet = $this->contractNet($employee, $start, $indicator)) > 0) {
                $row = ['source' => 'contrato', 'net' => $contractNet] + $row;
            }

            if ($row['source'] !== null) {
                $nets[] = $row['net'] + $row['subsidy'];
            }
            $months[] = $row;
        }

        if ($nets === []) {
            return ['monthly' => null, 'daily' => null, 'detail' => ['months' => $months, 'minimum_applied' => false]];
        }

        $monthly = round(array_sum($nets) / count($nets), 2);
        $daily = round($monthly / 30, 2);
        $minimum = $indicator ? round((float) $indicator->imm_value * 0.5 / 30, 2) : 0.0;

        return [
            'monthly' => $monthly,
            'daily' => max($daily, $minimum),
            'detail' => ['months' => $months, 'minimum_applied' => $minimum > $daily],
        ];
    }

    /** Remuneración neta de una liquidación: imponible con tope menos cotizaciones legales e impuesto. */
    private function netOf(Payroll $payroll): int
    {
        $indicator = MonthlyIndicator::query()
            ->where('year', $payroll->period_year)
            ->where('month', $payroll->period_month)
            ->first();
        $cap = (float) ($indicator?->getAfpCapClp() ?? 0);
        $taxable = (float) $payroll->gross_salary;
        $taxable = $cap > 0 ? min($taxable, $cap) : $taxable;

        // De la salud sólo se descuenta la cotización legal: el exceso del plan de Isapre es voluntario.
        $health = min((float) $payroll->isapre_amount, round($taxable * self::HEALTH_RATE));

        return (int) max(0, round(
            $taxable - (float) $payroll->afp_amount - $health - (float) $payroll->cesantia_amount - (float) $payroll->impuesto_unico_amount
        ));
    }

    /** Subsidio estimado de licencias anteriores que cae dentro del mes indicado. */
    private function subsidyAccruedIn(Employee $employee, CarbonImmutable $month, CarbonImmutable $before, ?int $exceptLeaveId): int
    {
        $monthEnd = $month->endOfMonth()->startOfDay();
        $total = 0.0;

        $leaves = MedicalLeave::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('status', '!=', MedicalLeave::STATUS_CANCELLED)
            ->where('start_date', '<', $before->toDateString())
            ->where('start_date', '<=', $monthEnd->toDateString())
            ->where('end_date', '>=', $month->toDateString())
            ->when($exceptLeaveId, fn ($query) => $query->whereKeyNot($exceptLeaveId))
            ->get();

        foreach ($leaves as $leave) {
            $end = $leave->effectiveEndDate();
            if ($end === null || $leave->subsidy_days <= 0 || $leave->subsidy_daily_amount === null) {
                continue;
            }
            // La carencia se descuenta al comienzo: los días subsidiados son los últimos de la licencia.
            $from = $end->subDays($leave->subsidy_days - 1);
            $from = $from->lt($month) ? $month : $from;
            $to = $end->gt($monthEnd) ? $monthEnd : $end;
            if ($from->lte($to)) {
                $total += ((int) $from->diffInDays($to) + 1) * (float) $leave->subsidy_daily_amount;
            }
        }

        return (int) round($total);
    }

    /** Remuneración neta que resulta del sueldo pactado en el contrato vigente al inicio de la licencia. */
    private function contractNet(Employee $employee, CarbonImmutable $start, MonthlyIndicator $indicator): int
    {
        $terms = EmploymentContract::inForceFor($employee->id, $start);
        $salary = (float) ($terms && (float) $terms->base_salary > 0 ? $terms->base_salary : $employee->salary);
        if ($salary <= 0) {
            return 0;
        }

        $pensionCap = (float) $indicator->getAfpCapClp();
        $afcCap = (float) $indicator->getAfcCapClp();
        $taxable = $pensionCap > 0 ? min($salary, $pensionCap) : $salary;
        $employee->loadMissing('afp');

        $afp = round($taxable * $this->engine->afpRate($employee, $indicator) / 100);
        $health = round($taxable * self::HEALTH_RATE);
        $contractType = ($terms?->contract_type ?: $employee->contract_type) ?: EmploymentContract::TYPE_INDEFINITE;
        $afc = $contractType === EmploymentContract::TYPE_INDEFINITE
            ? round(($afcCap > 0 ? min($salary, $afcCap) : $salary) * (float) $indicator->afc_indefinite_employee_rate / 100)
            : 0;
        $tax = (float) $indicator->calculateIut(max(0, $taxable - $afp - $health - $afc));

        return (int) max(0, round($taxable - $afp - $health - $afc - $tax));
    }

    /** Indicador del mes de inicio o, si aún no existe, el más reciente anterior. */
    private function indicatorAt(CarbonImmutable $date): ?MonthlyIndicator
    {
        return MonthlyIndicator::query()
            ->where(fn ($query) => $query
                ->where('year', '<', $date->year)
                ->orWhere(fn ($q) => $q->where('year', $date->year)->where('month', '<=', $date->month)))
            ->where('imm_value', '>', 0)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();
    }
}
