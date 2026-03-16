<?php
/**
 * Clase que representa el resultado del cálculo de la nómina de un empleado
 * @author Cristian Cartes
 * @package Modules\Payroll\DTOs
 */

namespace Modules\Payroll\DTOs;

class PayrollResultDTO
{
    public function __construct(
        public int $employee_id,
        public int $period_id,
        public float $base_salary,
        public float $worked_days,
        public float $proportional_salary,
        public float $gratification,
        public float $taxable_earnings,
        public float $non_taxable_earnings,
        public float $total_earnings,
        public array $earnings_details,
        public float $afp_amount,
        public float $health_amount,
        public float $afc_amount,
        public float $iusc_amount,
        public float $total_deductions,
        public array $deductions_details,
        public float $net_salary
    ) {
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employee_id,
            'period_id' => $this->period_id,
            'summary' => [
                'base_salary' => $this->formatCurrency($this->base_salary),
                'worked_days' => $this->worked_days,
                'proportional_salary' => $this->formatCurrency($this->proportional_salary),
                'gratification' => $this->formatCurrency($this->gratification),
                'taxable_earnings' => $this->formatCurrency($this->taxable_earnings),
                'non_taxable_earnings' => $this->formatCurrency($this->non_taxable_earnings),
                'total_earnings' => $this->formatCurrency($this->total_earnings),
                'total_deductions' => $this->formatCurrency($this->total_deductions),
                'net_salary' => $this->formatCurrency($this->net_salary),
            ],
            'details' => [
                'earnings' => array_map(fn($item) => array_merge($item, ['formatted_amount' => $this->formatCurrency($item['amount'])]), $this->earnings_details),
                'deductions' => array_map(fn($item) => array_merge($item, ['formatted_amount' => $this->formatCurrency($item['amount'])]), $this->deductions_details),
            ],
            'legal_deductions' => [
                'afp' => $this->formatCurrency($this->afp_amount),
                'health' => $this->formatCurrency($this->health_amount),
                'afc' => $this->formatCurrency($this->afc_amount),
                'iusc' => $this->formatCurrency($this->iusc_amount),
            ],
            // También mantenemos los valores brutos (raw) para cálculos si es necesario
            'raw' => [
                'total_earnings' => (int) $this->total_earnings,
                'total_deductions' => (int) $this->total_deductions,
                'net_salary' => (int) $this->net_salary,
            ]
        ];
    }

    private function formatCurrency(float $amount): string
    {
        return '$' . number_format(round($amount), 0, ',', '.');
    }
}
