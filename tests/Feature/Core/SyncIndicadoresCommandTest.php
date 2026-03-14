<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Modules\AdminPanel\Models\LegalParameter;
use Modules\Core\Models\BcchCredential;
use Modules\Core\Models\DailyUfValue;
use Modules\Core\Models\MonthlyIpcValue;
use Tests\TestCase;

class SyncIndicadoresCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_syncs_ipc_and_generates_daily_uf_values(): void
    {
        BcchCredential::query()->create([
            'username' => 'usuario.bcch',
            'password' => 'secreto-123',
        ]);

        Http::fake(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            if (($query['timeseries'] ?? null) === 'G073.IPC.VAR.2023.M') {
                return Http::response([
                    'Codigo' => 0,
                    'Descripcion' => 'Success',
                    'Series' => [
                        'descripEsp' => 'IPC mensual',
                        'descripIng' => 'Monthly CPI',
                        'seriesId' => 'G073.IPC.VAR.2023.M',
                        'Obs' => [
                            [
                                'indexDateString' => '28-02-2026',
                                'value' => '1.2000',
                                'statusCode' => 'OK',
                            ],
                        ],
                    ],
                    'SeriesInfos' => [],
                ]);
            }

            if (($query['timeseries'] ?? null) === 'F073.UFF.PRE.Z.D' && ($query['firstdate'] ?? null) === '2026-03-09') {
                return Http::response([
                    'Codigo' => 0,
                    'Descripcion' => 'Success',
                    'Series' => [
                        'descripEsp' => 'UF diaria',
                        'descripIng' => 'Daily UF',
                        'seriesId' => 'F073.UFF.PRE.Z.D',
                        'Obs' => [
                            [
                                'indexDateString' => '09-03-2026',
                                'value' => '39000.00',
                                'statusCode' => 'OK',
                            ],
                        ],
                    ],
                    'SeriesInfos' => [],
                ]);
            }

            if (($query['timeseries'] ?? null) === 'F073.UFF.PRE.Z.D' && ($query['firstdate'] ?? null) === '2026-03-08') {
                return Http::response([
                    'Codigo' => 0,
                    'Descripcion' => 'Success',
                    'Series' => [
                        'descripEsp' => 'UF diaria',
                        'descripIng' => 'Daily UF',
                        'seriesId' => 'F073.UFF.PRE.Z.D',
                        'Obs' => [
                            [
                                'indexDateString' => '08-03-2026',
                                'value' => '38990.00',
                                'statusCode' => 'OK',
                            ],
                        ],
                    ],
                    'SeriesInfos' => [],
                ]);
            }

            return Http::response([
                'Codigo' => -1,
                'Descripcion' => 'Unexpected request',
                'Series' => ['Obs' => []],
                'SeriesInfos' => [],
            ], 400);
        });

        $exitCode = Artisan::call('remsis:sync-indicadores', [
            '--for-date' => '2026-03-08',
        ]);

        $this->assertSame(0, $exitCode);

        $ipc = MonthlyIpcValue::query()->firstOrFail();

        $this->assertSame(2026, $ipc->year);
        $this->assertSame(2, $ipc->month);
        $this->assertSame('1.2000', $ipc->value);
        $this->assertCount(31, DailyUfValue::query()->get());

        $firstDay = DailyUfValue::query()->whereDate('date', '2026-03-10')->firstOrFail();
        $lastDay = DailyUfValue::query()->whereDate('date', '2026-04-09')->firstOrFail();

        $factor = pow(1 + (1.2 / 100), 1 / 31);
        $expectedUf = round(39000.00, 2);

        for ($i = 0; $i < 31; $i++) {
            $expectedUf = round($expectedUf * $factor, 2);

            if ($i === 0) {
                $this->assertSame(number_format($expectedUf, 2, '.', ''), $firstDay->value);
            }
        }

        $this->assertSame(number_format($expectedUf, 2, '.', ''), $lastDay->value);

        $this->assertSame('1.2', LegalParameter::query()->where('key', 'ipc_value')->value('value'));
        $this->assertSame('38990.00', LegalParameter::query()->where('key', 'uf_value')->value('value'));
    }

    public function test_command_rounds_bcch_ipc_to_official_published_precision(): void
    {
        BcchCredential::query()->create([
            'username' => 'usuario.bcch',
            'password' => 'secreto-123',
        ]);

        Http::fake(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            if (($query['timeseries'] ?? null) === 'G073.IPC.VAR.2023.M') {
                return Http::response([
                    'Codigo' => 0,
                    'Descripcion' => 'Success',
                    'Series' => [
                        'seriesId' => 'G073.IPC.VAR.2023.M',
                        'Obs' => [[
                            'indexDateString' => '28-02-2026',
                            'value' => '-0.00946499530360256',
                            'statusCode' => 'OK',
                        ]],
                    ],
                    'SeriesInfos' => [],
                ]);
            }

            if (($query['timeseries'] ?? null) === 'F073.UFF.PRE.Z.D' && ($query['firstdate'] ?? null) === '2026-03-09') {
                return Http::response([
                    'Codigo' => 0,
                    'Descripcion' => 'Success',
                    'Series' => [
                        'seriesId' => 'F073.UFF.PRE.Z.D',
                        'Obs' => [[
                            'indexDateString' => '09-03-2026',
                            'value' => '39841.72',
                            'statusCode' => 'OK',
                        ]],
                    ],
                    'SeriesInfos' => [],
                ]);
            }

            if (($query['timeseries'] ?? null) === 'F073.UFF.PRE.Z.D' && ($query['firstdate'] ?? null) === '2026-03-08') {
                return Http::response([
                    'Codigo' => 0,
                    'Descripcion' => 'Success',
                    'Series' => [
                        'seriesId' => 'F073.UFF.PRE.Z.D',
                        'Obs' => [[
                            'indexDateString' => '08-03-2026',
                            'value' => '39841.72',
                            'statusCode' => 'OK',
                        ]],
                    ],
                    'SeriesInfos' => [],
                ]);
            }

            return Http::response([
                'Codigo' => -1,
                'Descripcion' => 'Unexpected request',
                'Series' => ['Obs' => []],
                'SeriesInfos' => [],
            ], 400);
        });

        $exitCode = Artisan::call('remsis:sync-indicadores', [
            '--for-date' => '2026-03-08',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('0.0000', MonthlyIpcValue::query()->firstOrFail()->value);
        $this->assertSame('0.0', LegalParameter::query()->where('key', 'ipc_value')->value('value'));
        $this->assertSame('39841.72', DailyUfValue::query()->whereDate('date', '2026-03-10')->value('value'));
        $this->assertSame('39841.72', DailyUfValue::query()->whereDate('date', '2026-04-09')->value('value'));
    }
}
