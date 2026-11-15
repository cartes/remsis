<?php

namespace Modules\Payroll\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\MonthlyIndicator;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmploymentContract;
use Modules\Payroll\Exceptions\PayrollException;
use Modules\Payroll\Models\EmployeeItem;
use Modules\Payroll\Models\Item;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollDetail;
use Modules\Payroll\Models\PayrollPeriod;

/**
 * Motor único de cálculo de remuneraciones.
 *
 * Todos los parámetros legales (UF, UTM, topes imponibles, tasas, tramos del impuesto único
 * y de asignación familiar) se leen del MonthlyIndicator del período. Si no existe, no se calcula.
 *
 * Recalcular es repetible: con las mismas entradas produce los mismos importes y cobra
 * como máximo una cuota por crédito y período. Los períodos cerrados o pagados no se tocan.
 */
class PayrollCalculationService
{
    /** Conceptos de detalle ingresados a mano en el wizard; el motor no los regenera. */
    public const MANUAL_DETAIL_CONCEPTS = ['CO01', 'SC01', 'BI01', 'AG01'];

    /** Tasa legal de cotización de salud. */
    private const HEALTH_RATE = 0.07;

    /** Tope mensual del APV que rebaja la base tributable (Art. 42 bis LIR). */
    private const APV_MONTHLY_CAP_UF = 50;

    /**
     * Calcula la nómina de toda la dotación vigente en el período.
     *
     * @return int Cantidad de liquidaciones generadas o actualizadas
     */
    public function calculatePeriod(PayrollPeriod $period): int
    {
        return DB::transaction(function () use ($period) {
            $locked = $this->lockEditablePeriod($period);
            $indicator = $this->indicatorFor($locked);

            $employees = $this->headcount($locked);
            foreach ($employees as $employee) {
                $this->compute($employee, $locked, $indicator);
            }

            $locked->status = PayrollPeriod::STATUS_CALCULATED;
            $locked->save();
            $period->setRawAttributes($locked->getAttributes(), true);

            return $employees->count();
        });
    }

    /**
     * Calcula (o recalcula) la liquidación de un colaborador.
     */
    public function calculateEmployee(Employee $employee, PayrollPeriod $period): Payroll
    {
        return DB::transaction(function () use ($employee, $period) {
            $locked = $this->lockEditablePeriod($period);

            return $this->compute($employee, $locked, $this->indicatorFor($locked));
        });
    }

    /**
     * Bloquea la fila del período y verifica que admita cambios, de modo que un cálculo
     * simultáneo con el cierre no pueda escribir sobre un período ya cerrado.
     */
    public function lockEditablePeriod(PayrollPeriod $period): PayrollPeriod
    {
        $locked = PayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();

        if (! $locked->canModify()) {
            throw PayrollException::periodLocked($locked);
        }

        return $locked;
    }

    private function indicatorFor(PayrollPeriod $period): MonthlyIndicator
    {
        $indicator = MonthlyIndicator::query()
            ->where('year', $period->year)
            ->where('month', $period->month)
            ->first();

        if (! $indicator || $indicator->uf_value <= 0 || $indicator->utm_value <= 0 || $indicator->imm_value <= 0) {
            throw PayrollException::missingIndicators($period);
        }

        return $indicator;
    }

    /**
     * Dotación del período: colaboradores en nómina con vínculo vigente en algún día del mes,
     * incluidos quienes ingresaron o fueron desvinculados durante él.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    private function headcount(PayrollPeriod $period)
    {
        return Employee::query()
            ->where('company_id', $period->company_id)
            ->where('is_in_payroll', true)
            ->where(function ($query) use ($period) {
                $query->whereNull('hire_date')
                    ->orWhere('hire_date', '<=', $period->end_date->toDateString());
            })
            ->where(function ($query) use ($period) {
                $query->where('status', 'active')
                    ->orWhereHas('contracts', function ($contracts) use ($period) {
                        $contracts->whereNotNull('termination_date')
                            ->where('termination_date', '>=', $period->start_date->toDateString());
                    });
            })
            ->with(['employeeItems.item', 'afp'])
            ->get();
    }

    private function compute(Employee $employee, PayrollPeriod $period, MonthlyIndicator $indicator): Payroll
    {
        $employee->loadMissing(['employeeItems.item', 'afp']);

        $line = Payroll::where('employee_id', $employee->id)
            ->where('payroll_period_id', $period->id)
            ->first();

        $earnings = new MonthlyPayrollEarnings($employee, $period, $line, $indicator);
        $resolve = fn (EmployeeItem $ei): int => $ei->resolvedAmountCLP($earnings->uf, $earnings->utm, $earnings->salary, $earnings->workedDays);

        // ── Entradas manuales de la línea (no se derivan de ítems, por eso no se acumulan) ──
        $anticipos = (int) round((float) $line?->anticipos_amount);
        $otrosDescuentos = (int) round((float) $line?->otros_descuentos);

        // ── Ítems del colaborador ──
        $assignments = $employee->employeeItems->filter(fn ($ei) => $ei->item !== null);
        $active = $assignments->filter(fn ($ei) => (bool) $ei->is_active);

        $nonTaxableItems = $active->filter(fn ($ei) => $ei->item->type === Item::TYPE_HABER_NO_IMPONIBLE);
        $itemDeductions = $active->filter(fn ($ei) => $ei->item->type === Item::TYPE_DESCUENTO_VARIOS);
        [$credits, $chargedInstallments] = $this->creditsFor($assignments, $line);

        $totalNonTaxableItems = (int) $nonTaxableItems->sum($resolve);
        $totalItemDeductions = (int) $itemDeductions->sum($resolve);
        $totalCredits = (int) $credits->sum($resolve);
        $itemsByCode = fn (string $code): int => (int) $nonTaxableItems->filter(fn ($ei) => $ei->item->code === $code)->sum($resolve);

        // ── Bases imponibles con tope ──
        $taxable = $earnings->taxable;
        $pensionCap = (float) $indicator->getAfpCapClp();
        $afcCap = (float) $indicator->getAfcCapClp();
        $pensionBase = $pensionCap > 0 ? min($taxable, $pensionCap) : $taxable;
        $afcBase = $afcCap > 0 ? min($taxable, $afcCap) : $taxable;

        // ── Descuentos legales ──
        $afpAmount = (int) round($pensionBase * $this->afpRate($employee, $indicator) / 100);

        $legalHealth = (int) round($pensionBase * self::HEALTH_RATE);
        $healthAmount = max($legalHealth, $this->isaprePlan($employee));

        $contractType = $employee->contract_type ?: EmploymentContract::TYPE_INDEFINITE;
        $isIndefinite = $contractType === EmploymentContract::TYPE_INDEFINITE;
        $afcExempt = (bool) EmploymentContract::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->value('is_afc_exempt');

        // Seguro de cesantía: el trabajador sólo cotiza con contrato indefinido (Ley 19.728).
        $afcEmployee = (! $afcExempt && $isIndefinite)
            ? (int) round($afcBase * (float) $indicator->afc_indefinite_employee_rate / 100)
            : 0;
        $afcEmployer = $afcExempt ? 0 : (int) round(
            $afcBase * (float) ($isIndefinite ? $indicator->afc_indefinite_employer_rate : $indicator->afc_fixed_employer_rate) / 100
        );

        // ── APV e impuesto único ──
        $apvAmount = (int) round(min((float) $employee->apv_amount, self::APV_MONTHLY_CAP_UF * (float) $indicator->uf_value));

        // La salud rebaja la base tributable hasta el 7% del tope imponible.
        $deductibleHealth = $pensionCap > 0 ? min($healthAmount, (int) round($pensionCap * self::HEALTH_RATE)) : $healthAmount;
        $tributableBase = max(0, $taxable - $afpAmount - $deductibleHealth - $afcEmployee - $apvAmount);
        $taxAmount = (int) $indicator->calculateIut((float) $tributableBase);

        // ── Asignación familiar (no imponible ni tributable) ──
        $familyAllowance = (int) $employee->num_dependents > 0
            ? (int) round((int) $employee->num_dependents * $indicator->getFamilyAllowancePerDependent((float) $taxable))
            : 0;

        // ── Totales ──
        $nonTaxableEarnings = $totalNonTaxableItems + $familyAllowance;
        $totalEarnings = $taxable + $nonTaxableEarnings;
        $totalDeductions = $afpAmount + $healthAmount + $afcEmployee + $taxAmount + $apvAmount
            + $anticipos + $otrosDescuentos + $totalItemDeductions + $totalCredits;

        $payroll = Payroll::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
            ],
            [
                'user_id' => $employee->user_id,
                'company_id' => $period->company_id,
                'period_year' => $period->year,
                'period_month' => $period->month,
                'worked_days' => $earnings->workedDays,

                'overtime_hours' => $earnings->overtimeHours,
                'overtime_amount' => $earnings->overtimeAmount,
                'comisiones_amount' => (int) $line?->comisiones_amount,
                'semana_corrida_amount' => (int) $line?->semana_corrida_amount,
                'bonos_imponibles_amount' => (int) $line?->bonos_imponibles_amount,
                'aguinaldos_amount' => (int) $line?->aguinaldos_amount,

                'base_salary' => (int) $employee->salary,
                'gratification_amount' => $earnings->gratification,
                'gross_salary' => $taxable,
                'meal_allowance' => $itemsByCode('COLACION'),
                'mobility_allowance' => $itemsByCode('MOVILIZACION'),
                'asignacion_familiar_amount' => $familyAllowance,
                'non_taxable_earnings' => $nonTaxableEarnings,
                'total_earnings' => $totalEarnings,

                'afp_id' => $employee->afp_id,
                'afp_amount' => $afpAmount,
                'isapre_id' => $employee->isapre_id,
                'isapre_amount' => $healthAmount,
                'ccaf_id' => $employee->ccaf_id,
                'ccaf_amount' => 0,
                'cesantia_amount' => $afcEmployee,
                'apv_amount' => $apvAmount,
                'tributable_base' => $tributableBase,
                'impuesto_unico_amount' => $taxAmount,

                'anticipos_amount' => $anticipos,
                'otros_descuentos' => $otrosDescuentos,
                'descuentos_items_amount' => $totalItemDeductions,
                'creditos_amount' => $totalCredits,

                'total_deductions' => $totalDeductions,
                'net_salary' => $totalEarnings - $totalDeductions,

                'employer_sis_amount' => (int) round($pensionBase * (float) $indicator->sis_rate / 100),
                'employer_afc_amount' => $afcEmployer,
                'employer_mutual_amount' => (int) round(
                    $pensionBase * ((float) $indicator->mutual_base_rate + (float) $indicator->sanna_rate) / 100
                ),

                'status' => 'pending',
            ]
        );

        // ── Desglose de la liquidación ──
        $rows = [
            $this->row('earning', 'SUELDO_BASE', "Sueldo base ({$earnings->workedDays} días)", $earnings->salary),
            $this->row('earning', 'HORAS_EXTRA', "Horas extraordinarias ({$earnings->overtimeHours} h)", $earnings->overtimeAmount),
            $this->row('earning', 'GRATIFICACION', 'Gratificación', $earnings->gratification),
            $this->row('earning', 'ASIG_FAMILIAR', "Asignación familiar ({$employee->num_dependents} cargas)", $familyAllowance),
            $this->row('deduction', 'AFP', 'Cotización AFP'.($employee->afp ? ' '.$employee->afp->nombre : ''), $afpAmount),
            $this->row('deduction', 'SALUD', 'Cotización de salud', $healthAmount),
            $this->row('deduction', 'AFC', 'Seguro de cesantía', $afcEmployee),
            $this->row('deduction', 'IUSC', 'Impuesto único de segunda categoría', $taxAmount),
            $this->row('deduction', 'APV', 'Ahorro previsional voluntario', $apvAmount),
            $this->row('deduction', 'ANTICIPO', 'Anticipos', $anticipos),
            $this->row('deduction', 'OTROS_DESCUENTOS', 'Otros descuentos', $otrosDescuentos),
        ];
        foreach ($earnings->taxableItemLines as $itemLine) {
            $rows[] = $this->row('earning', 'ITEM_IMPONIBLE', $itemLine['assignment']->item->name, $itemLine['amount'], $itemLine['assignment']->id);
        }
        foreach ($nonTaxableItems as $ei) {
            $rows[] = $this->row('earning', 'ITEM_NO_IMPONIBLE', $ei->item->name, $resolve($ei), $ei->id);
        }
        foreach ($itemDeductions as $ei) {
            $rows[] = $this->row('deduction', 'DESCUENTO', $ei->item->name, $resolve($ei), $ei->id);
        }
        foreach ($credits as $ei) {
            $installment = $this->chargeInstallment($ei, $chargedInstallments);
            $label = $ei->item->name.' (cuota '.$installment.($ei->total_installments ? '/'.$ei->total_installments : '').')';
            $rows[] = $this->row('deduction', 'CREDITO', $label, $resolve($ei), $ei->id, $installment);
        }

        $payroll->details()->whereNotIn('concept', self::MANUAL_DETAIL_CONCEPTS)->delete();
        foreach (array_filter($rows) as $row) {
            PayrollDetail::create(['payroll_id' => $payroll->id] + $row);
        }

        return $payroll;
    }

    /**
     * Créditos a cobrar en el período: los activos y los que ya se cobraron en esta liquidación
     * y quedaron inactivos por completar su última cuota. Si un crédito cobrado fue quitado o
     * desactivado a mano, se devuelve la cuota que se le había contado.
     *
     * @return array{0: Collection<int, EmployeeItem>, 1: Collection<int, int>}
     */
    private function creditsFor(Collection $assignments, ?Payroll $line): array
    {
        $charged = $line
            ? $line->details()->where('concept', 'CREDITO')->whereNotNull('employee_item_id')
                ->pluck('installment_number', 'employee_item_id')
            : collect();

        $credits = $assignments->filter(fn ($ei) => $ei->item->type === Item::TYPE_CREDITO
            && ($ei->is_active || ($charged->has($ei->id) && $ei->isFullyPaid())));

        foreach ($charged->keys()->diff($credits->pluck('id')) as $removedId) {
            $removed = $assignments->firstWhere('id', $removedId);
            if ($removed && $removed->current_installment > 0) {
                $removed->update(['current_installment' => $removed->current_installment - 1]);
            }
        }

        return [$credits, $charged];
    }

    /**
     * Número de cuota que corresponde a este período. Sólo avanza el contador del crédito
     * la primera vez que se cobra en la liquidación; los recálculos reutilizan la misma cuota.
     */
    private function chargeInstallment(EmployeeItem $credit, Collection $chargedInstallments): int
    {
        if ($chargedInstallments->has($credit->id)) {
            return (int) $chargedInstallments->get($credit->id);
        }

        $installment = (int) $credit->current_installment + 1;
        $updates = ['current_installment' => $installment];
        if ($credit->total_installments !== null && $installment >= $credit->total_installments) {
            $updates['is_active'] = false;
        }
        $credit->update($updates);

        return $installment;
    }

    /**
     * Tasa total de la AFP (10% + comisión). Prefiere la tasa registrada en el indicador
     * del período, que conserva el valor histórico; si no está, usa el catálogo de AFP.
     */
    private function afpRate(Employee $employee, MonthlyIndicator $indicator): float
    {
        $afp = $employee->afp;
        if (! $afp) {
            return 10.0;
        }

        $code = mb_strtolower(trim((string) $afp->code));
        $name = mb_strtolower(trim((string) $afp->nombre));
        foreach ((array) $indicator->afp_rates as $key => $rate) {
            $rateCode = mb_strtolower((string) ($rate['afp_code'] ?? $key));
            $rateName = mb_strtolower((string) ($rate['afp_name'] ?? ''));
            if (($code !== '' && $rateCode === $code) || ($name !== '' && $rateName === $name)) {
                return (float) ($rate['total_rate'] ?? ((float) ($rate['mandatory_rate'] ?? 10.0) + (float) ($rate['commission_rate'] ?? 0.0)));
            }
        }

        return (float) ($afp->rate > 0 ? $afp->rate : 10.0) + (float) $afp->commission;
    }

    /**
     * Monto en pesos del plan pactado con la Isapre. Fonasa cotiza sólo el 7% legal.
     */
    private function isaprePlan(Employee $employee): int
    {
        $isIsapre = $employee->health_system === 'isapre'
            || ($employee->health_system === null && $employee->isapre_id !== null);

        return $isIsapre ? (int) round((float) $employee->health_contribution) : 0;
    }

    private function row(string $type, string $concept, string $description, int $amount, ?int $employeeItemId = null, ?int $installment = null): ?array
    {
        if ($amount === 0) {
            return null;
        }

        return [
            'type' => $type,
            'concept' => $concept,
            'description' => $description,
            'amount' => $amount,
            'employee_item_id' => $employeeItemId,
            'installment_number' => $installment,
        ];
    }
}
