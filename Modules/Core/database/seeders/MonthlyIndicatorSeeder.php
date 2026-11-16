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

        $afpRates = $service->buildAfpRatesPayload($period);
        $iutBrackets = $service->buildDefaultIutBrackets();
        $familyBrackets = $service->buildDefaultFamilyAllowanceBrackets($period);

        $indicator = MonthlyIndicator::query()->updateOrCreate(
            ['period' => $period],
            $service->legalRatesFor($period) + [
                'year' => (int) $now->format('Y'),
                'month' => (int) $now->format('n'),
                'uf_value' => 38500.00,
                'uf_previous_month' => 38400.00,
                'utm_value' => 68034.00,
                'uta_value' => 816408.00,
                'ipc_value' => 0.40,
                'legal_weekly_hours' => $service->legalWeeklyHoursFor($period),
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

