<?php

namespace Tests\Feature\AdminPanel;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\Models\BcchCredential;
use Modules\Core\Models\DailyUfValue;
use Modules\Core\Models\MonthlyIpcValue;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BancoCentralSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_banco_central_settings(): void
    {
        $superAdmin = $this->createUserWithRole('super-admin');
        $ipc = MonthlyIpcValue::query()->create([
            'year' => 2026,
            'month' => 2,
            'reference_period' => '2026-02-01',
            'published_at' => '2026-03-08',
            'value' => 0.4500,
            'source_series' => 'G073.IPC.VAR.2023.M',
        ]);

        DailyUfValue::query()->create([
            'monthly_ipc_value_id' => $ipc->id,
            'date' => now()->startOfMonth()->toDateString(),
            'cycle_start_date' => now()->startOfMonth()->toDateString(),
            'cycle_end_date' => now()->endOfMonth()->toDateString(),
            'value' => 39000.12,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('settings.bcch'));

        $response->assertOk();
        $response->assertSee('Banco Central e Indicadores');
    }

    public function test_admin_cannot_view_banco_central_settings(): void
    {
        $admin = $this->createUserWithRole('admin');

        $response = $this->actingAs($admin)->get(route('settings.bcch'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_store_encrypted_credentials(): void
    {
        $superAdmin = $this->createUserWithRole('super-admin');

        $response = $this->actingAs($superAdmin)->put(route('settings.bcch.credentials.update'), [
            'username' => 'usuario.bcch',
            'password' => 'secreto-123',
        ]);

        $response->assertRedirect(route('settings.bcch'));

        $credential = BcchCredential::query()->firstOrFail();

        $this->assertSame('usuario.bcch', $credential->username);
        $this->assertSame('secreto-123', $credential->password);
        $this->assertNotSame('usuario.bcch', (string) \DB::table('bcch_credentials')->value('username'));
        $this->assertNotSame('secreto-123', (string) \DB::table('bcch_credentials')->value('password'));
    }

    public function test_super_admin_sees_manual_sync_button_when_today_uf_is_missing(): void
    {
        $superAdmin = $this->createUserWithRole('super-admin');

        BcchCredential::query()->create([
            'username' => 'usuario.bcch',
            'password' => 'secreto-123',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('settings.bcch'));

        $response->assertOk();
        $response->assertSee('Sincronizar UF de hoy');
    }

    public function test_super_admin_can_manually_sync_today_uf(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        $superAdmin = $this->createUserWithRole('super-admin');

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

        $response = $this->actingAs($superAdmin)->post(route('settings.bcch.sync_today'));

        $response->assertRedirect(route('settings.bcch'));
        $response->assertSessionHas('success_bcch');
        $this->assertSame('39841.72', DailyUfValue::query()->whereDate('date', '2026-03-14')->value('value'));
        $this->assertSame('39841.72', \Modules\AdminPanel\Models\LegalParameter::query()->where('key', 'uf_value')->value('value'));

        CarbonImmutable::setTestNow();
    }

    public function test_super_admin_can_manually_sync_today_uf_when_ipc_fails_but_cached_value_exists(): void
    {
        CarbonImmutable::setTestNow('2026-03-14 09:00:00');

        $superAdmin = $this->createUserWithRole('super-admin');

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

        $response = $this->actingAs($superAdmin)->post(route('settings.bcch.sync_today'));

        $response->assertRedirect(route('settings.bcch'));
        $response->assertSessionHas('success_bcch');
        $this->assertSame('39841.72', DailyUfValue::query()->whereDate('date', '2026-03-14')->value('value'));

        CarbonImmutable::setTestNow();
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
