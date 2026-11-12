<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Modules\Core\Models\MonthlyIndicator;
use Modules\Core\Services\MonthlyIndicatorSyncService;

class MonthlyIndicatorSeeder extends Seeder
{
    /**
     * Crea el registro base de indicadores previsionales mensuales sin requerir red externa.
     */
    public function run(): void
    {
        $service = app(MonthlyIndicatorSyncService::class);
        $now = CarbonImmutable::now('America/Santiago');
        $period = $now->format('Y-m');

        $afpRates = $service->buildAfpRatesPayload();
        $iutBrackets = $service->buildDefaultIutBrackets();
        $familyBrackets = $service->buildDefaultFamilyAllowanceBrackets();

        $indicator = MonthlyIndicator::query()->updateOrCreate(
            ['period' => $period],
            [
                'year' => (int) $now->format('Y'),
                'month' => (int) $now->format('n'),
                'uf_value' => 38500.00,
                'uf_previous_month' => 38400.00,
                'utm_value' => 68034.00,
                'uta_value' => 816408.00,
                'ipc_value' => 0.40,
                'imm_value' => 510636,
                'imm_under_18_over_65' => 380923,
                'imm_non_remunerational' => 329151,
                'cap_afp_uf' => 84.30,
                'cap_ips_uf' => 60.00,
                'cap_afc_uf' => 126.60,
                'sis_rate' => 1.49,
                'afc_indefinite_employee_rate' => 0.60,
                'afc_indefinite_employer_rate' => 2.40,
                'afc_fixed_employer_rate' => 3.00,
                'mutual_base_rate' => 0.90,
                'sanna_rate' => 0.03,
                'cesantia_pensionados_employer_rate' => 0.10,
                'legal_weekly_hours' => 42,
                'afp_rates' => $afpRates,
                'iut_brackets' => $iutBrackets,
                'family_allowance_brackets' => $familyBrackets,
                'source' => 'seeder',
                'synced_at' => now(),
            ]
        );

        foreach ($afpRates as $rate) {
            $indicator->afpRateRecords()->updateOrCreate(
                ['afp_code' => $rate['afp_code']],
                $rate
            );
        }

        foreach ($iutBrackets as $bracket) {
            $indicator->iutBracketRecords()->updateOrCreate(
                ['bracket_number' => $bracket['bracket_number']],
                $bracket
            );
        }

        foreach ($familyBrackets as $family) {
            $indicator->familyAllowanceBracketRecords()->updateOrCreate(
                ['bracket_letter' => $family['bracket_letter']],
                $family
            );
        }
    }
}

