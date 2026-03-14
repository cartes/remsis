<?php

namespace Tests\Feature\Core;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\Models\BcchCredential;
use Modules\Core\Models\DailyUfValue;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DailyUfAutoSyncOnLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_triggers_daily_uf_sync_when_missing(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($adminRole);

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
                            'value' => '0.0000',
                            'statusCode' => 'OK',
                        ]],
                    ],
                    'SeriesInfos' => [],
                ]);
            }

            if (($query['timeseries'] ?? null) === 'F073.UFF.PRE.Z.D') {
                return Http::response([
                    'Codigo' => 0,
                    'Descripcion' => 'Success',
                    'Series' => [
                        'seriesId' => 'F073.UFF.PRE.Z.D',
                        'Obs' => [[
                            'indexDateString' => '14-03-2026',
                            'value' => '39841.72',
                            'statusCode' => 'OK',
                        ]],
                    ],
                    'SeriesInfos' => [],
                ]);
            }

            return Http::response(['Codigo' => -1, 'Descripcion' => 'Unexpected'], 400);
        });

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertSame('39841.72', DailyUfValue::query()->whereDate('date', '2026-03-14')->value('value'));
        $this->assertSame('2026-03-14', BcchCredential::query()->firstOrFail()->last_daily_sync_attempted_for?->toDateString());

        CarbonImmutable::setTestNow();
    }

    public function test_login_only_attempts_automatic_sync_once_per_day(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($adminRole);

        BcchCredential::query()->create([
            'username' => 'usuario.bcch',
            'password' => 'secreto-123',
            'last_daily_sync_attempted_for' => '2026-03-14',
            'last_daily_sync_attempted_at' => '2026-03-14 08:00:00',
            'last_daily_sync_error' => 'Fallo previo.',
        ]);

        Http::fake();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
        Http::assertNothingSent();
        $this->assertNull(DailyUfValue::query()->whereDate('date', '2026-03-14')->value('value'));

        CarbonImmutable::setTestNow();
    }

    public function test_login_can_sync_today_uf_even_if_ipc_endpoint_fails_using_cached_ipc(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($adminRole);

        BcchCredential::query()->create([
            'username' => 'usuario.bcch',
            'password' => 'secreto-123',
        ]);

        Http::fake(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            if (($query['timeseries'] ?? null) === 'G073.IPC.VAR.2023.M') {
                return Http::response([
                    'Codigo' => -1,
                    'Descripcion' => 'An internal error has occurred, information is not available.',
                    'Series' => ['Obs' => null],
                    'SeriesInfos' => [],
                ]);
            }

            if (($query['timeseries'] ?? null) === 'F073.UFF.PRE.Z.D') {
                return Http::response([
                    'Codigo' => 0,
                    'Descripcion' => 'Success',
                    'Series' => [
                        'seriesId' => 'F073.UFF.PRE.Z.D',
                        'Obs' => [[
                            'indexDateString' => '14-03-2026',
                            'value' => '39841.72',
                            'statusCode' => 'OK',
                        ]],
                    ],
                    'SeriesInfos' => [],
                ]);
            }

            return Http::response(['Codigo' => -1, 'Descripcion' => 'Unexpected'], 400);
        });

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertSame('39841.72', DailyUfValue::query()->whereDate('date', '2026-03-14')->value('value'));
        $this->assertSame('legal_parameters_cache', \Modules\Core\Models\MonthlyIpcValue::query()->firstOrFail()->source_series);

        CarbonImmutable::setTestNow();
    }

    public function test_login_can_sync_today_uf_when_exact_uf_query_fails_but_range_query_returns_today(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($adminRole);

        BcchCredential::query()->create([
            'username' => 'usuario.bcch',
            'password' => 'secreto-123',
        ]);

        Http::fake(function ($request) {
            $query = [];
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            if (($query['timeseries'] ?? null) === 'G073.IPC.VAR.2023.M') {
                return Http::response([
                    'Codigo' => -1,
                    'Descripcion' => 'An internal error has occurred, information is not available.',
                    'Series' => ['Obs' => null],
                    'SeriesInfos' => [],
                ]);
            }

            if (($query['timeseries'] ?? null) === 'F073.UFF.PRE.Z.D'
                && ($query['firstdate'] ?? null) === '2026-03-14'
                && ($query['lastdate'] ?? null) === '2026-03-14') {
                return Http::response([
                    'Codigo' => -1,
                    'Descripcion' => 'An internal error has occurred, information is not available.',
                    'Series' => ['Obs' => null],
                    'SeriesInfos' => [],
                ]);
            }

            if (($query['timeseries'] ?? null) === 'F073.UFF.PRE.Z.D'
                && ($query['firstdate'] ?? null) === '2026-03-07'
                && ($query['lastdate'] ?? null) === '2026-03-14') {
                return Http::response([
                    'Codigo' => 0,
                    'Descripcion' => 'Success',
                    'Series' => [
                        'seriesId' => 'F073.UFF.PRE.Z.D',
                        'Obs' => [[
                            'indexDateString' => '14-03-2026',
                            'value' => '39841.72',
                            'statusCode' => 'OK',
                        ]],
                    ],
                    'SeriesInfos' => [],
                ]);
            }

            return Http::response(['Codigo' => -1, 'Descripcion' => 'Unexpected'], 400);
        });

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertSame('39841.72', DailyUfValue::query()->whereDate('date', '2026-03-14')->value('value'));

        CarbonImmutable::setTestNow();
    }
}
