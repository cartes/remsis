<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\Models\MonthlyIndicator;
use Tests\TestCase;

class MindicadorSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_monthly_indicators_command_fetches_from_mindicador_api_and_persists_records(): void
    {
        Http::fake([
            'https://mindicador.cl/api' => Http::response([
                'uf' => ['valor' => 38620.45],
                'utm' => ['valor' => 68300.00],
                'ipc' => ['valor' => 0.50],
                'dolar' => ['valor' => 950.20],
            ], 200),
            'https://mindicador.cl/api/uf/31-03-2026' => Http::response([
                'serie' => [['fecha' => '2026-03-31', 'valor' => 38650.10]],
            ], 200),
            'https://mindicador.cl/api/uf/28-02-2026' => Http::response([
                'serie' => [['fecha' => '2026-02-28', 'valor' => 38490.80]],
            ], 200),
            'https://mindicador.cl/api/utm/01-03-2026' => Http::response([
                'serie' => [['fecha' => '2026-03-01', 'valor' => 68300.00]],
            ], 200),
        ]);

        $this->artisan('remsis:sync-monthly-indicators', ['--period' => '2026-03'])
            ->assertSuccessful();

        $indicator = MonthlyIndicator::query()->where('period', '2026-03')->first();

        $this->assertNotNull($indicator);
        $this->assertSame('mindicador', $indicator->source);
        $this->assertSame(38650.10, $indicator->uf_value);
        $this->assertSame(38490.80, $indicator->uf_previous_month);
        $this->assertSame(68300.00, $indicator->utm_value);
        $this->assertSame(819600.00, $indicator->uta_value);
        $this->assertSame(0.50, $indicator->ipc_value);
        $this->assertCount(7, $indicator->afpRateRecords);
        $this->assertCount(8, $indicator->iutBracketRecords);
        $this->assertCount(4, $indicator->familyAllowanceBracketRecords);

        // Los valores legales salen de la tabla de vigencias (config core.legal_indicators), no del código.
        $legal = config('core.legal_indicators.periods.2026-11');
        $this->assertSame((float) $legal['imm_value'], $indicator->imm_value);
        $this->assertSame($legal['cap_afp_uf'], $indicator->cap_afp_uf);
        $this->assertSame($legal['cap_afc_uf'], $indicator->cap_afc_uf);
        $this->assertSame($legal['sis_rate'], $indicator->sis_rate);
        $this->assertSame(
            3.5,
            round($indicator->employer_afp_rate + $indicator->employer_protected_return_rate
                + $indicator->employer_life_expectancy_rate + $indicator->sis_rate, 2)
        );
        $this->assertSame(10.0 + $legal['afp_commissions']['habitat'], $indicator->getAfpRate('habitat'));
        $this->assertSame(
            (float) $legal['family_allowance'][0]['amount_per_dependent'],
            $indicator->getFamilyAllowancePerDependent(500000)
        );
    }

    public function test_sync_applies_the_legal_values_in_force_for_each_period(): void
    {
        config(['core.legal_indicators.periods.2027-01' => array_merge(
            config('core.legal_indicators.periods.2026-11'),
            ['imm_value' => 600000, 'cap_afp_uf' => 92.5]
        )]);
        Http::fake(['*' => Http::response([], 500)]);

        $service = app(\Modules\Core\Services\MonthlyIndicatorSyncService::class);
        $before = $service->syncPeriod('2026-12');
        $after = $service->syncPeriod('2027-02');

        $this->assertSame(553553.0, $before->imm_value);
        $this->assertSame(90.0, $before->cap_afp_uf);
        $this->assertSame(600000.0, $after->imm_value);
        $this->assertSame(92.5, $after->cap_afp_uf);
        $this->assertSame(42, $after->legal_weekly_hours);
        $this->assertSame(44, $service->legalWeeklyHoursFor('2026-04'));
    }

    public function test_sync_falls_back_to_cmf_when_mindicador_date_query_fails(): void
    {
        config(['services.cmf.key' => 'test-cmf-key']);

        Http::fake([
            'https://mindicador.cl/api*' => Http::response([], 500),
            'https://api.cmfchile.cl/api-sbifv3/recursos_api/uf/*' => Http::response([
                'UFs' => [['Valor' => '38.710,25', 'Fecha' => '2026-03-31']],
            ], 200),
            'https://api.cmfchile.cl/api-sbifv3/recursos_api/utm/*' => Http::response([
                'UTMs' => [['Valor' => '68.450,00', 'Fecha' => '2026-03-01']],
            ], 200),
        ]);

        $this->artisan('remsis:sync-monthly-indicators', ['--period' => '2026-03'])
            ->assertSuccessful();

        $indicator = MonthlyIndicator::query()->where('period', '2026-03')->firstOrFail();

        $this->assertSame(38710.25, $indicator->uf_value);
        $this->assertSame(68450.00, $indicator->utm_value);
    }
}

