<?php

namespace Tests\Support;

use Modules\Core\Models\MonthlyIndicator;

/**
 * Indicadores de referencia para pruebas de nómina. Son valores redondos elegidos para
 * que los montos esperados se puedan verificar a mano; no son los valores legales vigentes.
 */
trait CreatesMonthlyIndicator
{
    protected function createMonthlyIndicator(int $year, int $month, array $overrides = []): MonthlyIndicator
    {
        return MonthlyIndicator::updateOrCreate(
            ['period' => sprintf('%04d-%02d', $year, $month)],
            array_merge([
                'year' => $year,
                'month' => $month,
                'uf_value' => 40000,
                'uf_previous_month' => 40000,
                'utm_value' => 70000,
                'uta_value' => 840000,
                'imm_value' => 500000,
                'cap_afp_uf' => 90.0,      // 3.600.000
                'cap_afc_uf' => 135.0,     // 5.400.000
                'sis_rate' => 1.5,
                'afc_indefinite_employee_rate' => 0.6,
                'afc_indefinite_employer_rate' => 2.4,
                'afc_fixed_employer_rate' => 3.0,
                'mutual_base_rate' => 0.9,
                'sanna_rate' => 0.03,
                'legal_weekly_hours' => 42,
                'afp_rates' => [],
                'iut_brackets' => [
                    ['from_utm' => 0, 'to_utm' => 13.5, 'factor' => 0, 'deduction_utm' => 0],
                    ['from_utm' => 13.5, 'to_utm' => 30, 'factor' => 0.04, 'deduction_utm' => 0.54],
                    ['from_utm' => 30, 'to_utm' => 50, 'factor' => 0.08, 'deduction_utm' => 1.74],
                    ['from_utm' => 50, 'to_utm' => 70, 'factor' => 0.135, 'deduction_utm' => 4.49],
                    ['from_utm' => 70, 'to_utm' => 90, 'factor' => 0.23, 'deduction_utm' => 11.14],
                    ['from_utm' => 90, 'to_utm' => 120, 'factor' => 0.304, 'deduction_utm' => 17.8],
                    ['from_utm' => 120, 'to_utm' => 310, 'factor' => 0.35, 'deduction_utm' => 23.32],
                    ['from_utm' => 310, 'to_utm' => null, 'factor' => 0.4, 'deduction_utm' => 38.82],
                ],
                'family_allowance_brackets' => [
                    ['bracket_letter' => 'A', 'income_from' => 0, 'income_to' => 600000, 'amount_per_dependent' => 20000],
                    ['bracket_letter' => 'B', 'income_from' => 600001, 'income_to' => 900000, 'amount_per_dependent' => 12000],
                    ['bracket_letter' => 'C', 'income_from' => 900001, 'income_to' => 1400000, 'amount_per_dependent' => 4000],
                    ['bracket_letter' => 'D', 'income_from' => 1400001, 'income_to' => null, 'amount_per_dependent' => 0],
                ],
                'source' => 'test',
            ], $overrides)
        );
    }
}
