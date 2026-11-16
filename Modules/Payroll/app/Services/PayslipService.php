<?php

namespace Modules\Payroll\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Companies\Models\Company;
use Modules\Core\Models\MonthlyIndicator;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;

/**
 * Arma la liquidación de sueldo (comprobante del Art. 54 del Código del Trabajo) a partir
 * de una línea de nómina ya calculada. No recalcula nada: sólo ordena lo que guardó el motor.
 *
 * Quien llama es responsable de autorizar el acceso a la liquidación; por eso las
 * relaciones se cargan sin el filtro de tenant.
 */
class PayslipService
{
    /** Conceptos del desglose agrupados por sección de la liquidación. */
    private const SECTIONS = [
        'taxable' => ['SUELDO_BASE', 'HORAS_EXTRA', 'GRATIFICACION', 'CO01', 'SC01', 'BI01', 'AG01', 'ITEM_IMPONIBLE'],
        'non_taxable' => ['ASIG_FAMILIAR', 'ITEM_NO_IMPONIBLE'],
        'legal' => ['AFP', 'SALUD', 'AFC', 'IUSC'],
        'other' => ['APV', 'ANTICIPO', 'OTROS_DESCUENTOS', 'DESCUENTO', 'CREDITO'],
    ];

    private const MANUAL_LABELS = [
        'CO01' => 'Comisiones',
        'SC01' => 'Semana corrida',
        'BI01' => 'Bono imponible',
        'AG01' => 'Aguinaldo',
    ];

    private const CONTRACT_LABELS = [
        'indefinido' => 'Indefinido',
        'plazo_fijo' => 'Plazo fijo',
        'obra_faena' => 'Por obra o faena',
        'part_time' => 'Part-time',
    ];

    private const PAYMENT_LABELS = [
        'transferencia' => 'Transferencia bancaria',
        'cheque' => 'Cheque',
        'efectivo' => 'Efectivo',
    ];

    /**
     * @return array<string, mixed>
     */
    public function data(Payroll $payroll): array
    {
        $employee = Employee::withoutGlobalScopes()
            ->with(['afp', 'isapre', 'bank', 'costCenter' => fn ($costCenter) => $costCenter->withoutGlobalScopes()])
            ->findOrFail($payroll->employee_id);
        $company = Company::withoutGlobalScopes()->findOrFail($payroll->company_id);
        $period = $payroll->payroll_period_id
            ? PayrollPeriod::withoutGlobalScopes()->find($payroll->payroll_period_id)
            : null;
        $indicator = MonthlyIndicator::where('year', $payroll->period_year)
            ->where('month', $payroll->period_month)
            ->first();

        $sections = $this->sections($payroll);
        $net = (int) $payroll->net_salary;

        return [
            'payroll' => $payroll,
            'employee' => $employee,
            'company' => $company,
            'period' => $period,
            'periodLabel' => (PayrollPeriod::MONTH_NAMES[$payroll->period_month] ?? '').' '.$payroll->period_year,
            'isIssued' => $period !== null && in_array($period->status, Payroll::ISSUED_PERIOD_STATUSES, true),
            'sections' => $sections,
            'totals' => [
                'taxable' => (int) $payroll->gross_salary,
                'non_taxable' => (int) $payroll->total_earnings - (int) $payroll->gross_salary,
                'earnings' => (int) $payroll->total_earnings,
                'legal' => (int) $sections['legal']->sum('amount'),
                'other' => (int) $payroll->total_deductions - (int) $sections['legal']->sum('amount'),
                'deductions' => (int) $payroll->total_deductions,
                'net' => $net,
            ],
            'netInWords' => $this->inWords($net),
            'tributableBase' => (int) $payroll->tributable_base,
            'uf' => $indicator?->uf_value,
            'utm' => $indicator?->utm_value,
            'contractLabel' => self::CONTRACT_LABELS[$employee->contract_type] ?? ($employee->contract_type ?: '—'),
            'healthLabel' => $employee->health_system === 'isapre'
                ? ($employee->isapre?->nombre ?? 'Isapre')
                : 'Fonasa',
            'afpLabel' => $employee->afp?->nombre ?? 'No cotiza',
            'paymentLabel' => self::PAYMENT_LABELS[$employee->payment_method] ?? '—',
            'paymentDate' => $payroll->payment_date ?? $period?->payment_date,
            'employer' => [
                'Seguro de invalidez y sobrevivencia (SIS)' => (int) $payroll->employer_sis_amount,
                'Seguro de cesantía (empleador)' => (int) $payroll->employer_afc_amount,
                'Mutual / Ley SANNA' => (int) $payroll->employer_mutual_amount,
                'Aporte a cuenta individual (Ley 21.735)' => (int) $payroll->employer_afp_amount,
                'Seguro social previsional (Ley 21.735)' => (int) $payroll->employer_social_security_amount,
            ],
        ];
    }

    public function filename(Payroll $payroll): string
    {
        $employee = Employee::withoutGlobalScopes()->find($payroll->employee_id);
        $who = Str::slug($employee?->rut ?: ($employee?->full_name ?: 'colaborador'));

        return sprintf('liquidacion-%s-%04d-%02d.pdf', $who, $payroll->period_year, $payroll->period_month);
    }

    public function pdf(Payroll $payroll): \Barryvdh\DomPDF\PDF
    {
        // Sin subconjunto de fuente cada liquidación pesa cerca de 900 KB por incrustar la tipografía completa.
        return Pdf::loadView('payroll::payslips.pdf', $this->data($payroll))
            ->setPaper('letter')
            ->setOption('isFontSubsettingEnabled', true);
    }

    /**
     * @return array<string, Collection<int, array{label: string, amount: int}>>
     */
    private function sections(Payroll $payroll): array
    {
        $details = $payroll->details()->orderBy('id')->get();

        if ($details->isEmpty()) {
            return $this->sectionsFromColumns($payroll);
        }

        $sections = [];
        foreach (self::SECTIONS as $key => $concepts) {
            $sections[$key] = $details
                ->whereIn('concept', $concepts)
                ->sortBy(fn ($d) => array_search($d->concept, $concepts, true))
                ->map(fn ($d) => [
                    'label' => $d->description ?: (self::MANUAL_LABELS[$d->concept] ?? $d->concept),
                    'amount' => (int) $d->amount,
                ])
                ->values();
        }

        return $sections;
    }

    /**
     * Liquidaciones anteriores al desglose por concepto: se arman con los totales de la línea.
     *
     * @return array<string, Collection<int, array{label: string, amount: int}>>
     */
    private function sectionsFromColumns(Payroll $p): array
    {
        $rows = fn (array $pairs) => collect($pairs)
            ->map(fn ($amount, $label) => ['label' => $label, 'amount' => (int) $amount])
            ->filter(fn ($row) => $row['amount'] !== 0)
            ->values();

        $otherNonTaxable = (int) $p->non_taxable_earnings - (int) $p->meal_allowance - (int) $p->mobility_allowance;
        $knownTaxable = (int) $p->overtime_amount + (int) $p->gratification_amount + (int) $p->comisiones_amount
            + (int) $p->semana_corrida_amount + (int) $p->bonos_imponibles_amount + (int) $p->aguinaldos_amount;

        return [
            'taxable' => $rows([
                "Sueldo base ({$p->worked_days} días)" => (int) $p->gross_salary - $knownTaxable,
                'Horas extraordinarias' => $p->overtime_amount,
                'Gratificación' => $p->gratification_amount,
                'Comisiones' => $p->comisiones_amount,
                'Semana corrida' => $p->semana_corrida_amount,
                'Bonos imponibles' => $p->bonos_imponibles_amount,
                'Aguinaldos' => $p->aguinaldos_amount,
            ]),
            'non_taxable' => $rows([
                'Asignación familiar' => $p->asignacion_familiar_amount,
                'Colación' => $p->meal_allowance,
                'Movilización' => $p->mobility_allowance,
                'Otros haberes no imponibles' => max(0, $otherNonTaxable),
            ]),
            'legal' => $rows([
                'Cotización AFP' => $p->afp_amount,
                'Cotización de salud' => $p->isapre_amount,
                'Seguro de cesantía' => $p->cesantia_amount,
                'Impuesto único de segunda categoría' => $p->impuesto_unico_amount,
            ]),
            'other' => $rows([
                'Ahorro previsional voluntario' => $p->apv_amount,
                'Anticipos' => $p->anticipos_amount,
                'Otros descuentos' => $p->otros_descuentos,
                'Descuentos por conceptos' => $p->descuentos_items_amount,
                'Créditos' => $p->creditos_amount,
            ]),
        ];
    }

    private function inWords(int $amount): string
    {
        if (! class_exists(\NumberFormatter::class)) {
            return '';
        }

        // ICU intercala guiones opcionales (U+00AD) que en el PDF se verían como cortes de palabra.
        $words = str_replace("\u{00AD}", '', (new \NumberFormatter('es', \NumberFormatter::SPELLOUT))->format(abs($amount)));

        return ($amount < 0 ? 'menos ' : '').$words.' pesos';
    }
}
