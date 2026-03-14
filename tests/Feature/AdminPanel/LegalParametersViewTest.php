<?php

namespace Tests\Feature\AdminPanel;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\DailyUfValue;
use Modules\Core\Models\MonthlyIpcValue;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LegalParametersViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_see_synced_ipc_and_uf_in_legal_parameters(): void
    {
        $superAdmin = $this->createUserWithRole('super-admin');
        $today = CarbonImmutable::now('America/Santiago');

        $ipc = MonthlyIpcValue::query()->create([
            'year' => 2026,
            'month' => 3,
            'reference_period' => '2026-03-01',
            'published_at' => '2026-04-08',
            'value' => 1.2345,
            'source_series' => 'G073.IPC.VAR.2023.M',
        ]);

        DailyUfValue::query()->create([
            'monthly_ipc_value_id' => $ipc->id,
            'date' => $today->toDateString(),
            'cycle_start_date' => $today->startOfMonth()->day(10)->toDateString(),
            'cycle_end_date' => $today->addMonthNoOverflow()->startOfMonth()->day(9)->toDateString(),
            'value' => 39876.54,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('settings.legal'));

        $response->assertOk();
        $response->assertSee('Indicadores sincronizados');
        $response->assertSee('1,2%', false);
        $response->assertSee('39.876,54', false);
        $response->assertSee(route('settings.bcch'), false);
    }

    public function test_legal_parameters_view_shows_pending_sync_when_no_synced_indicators_exist(): void
    {
        $superAdmin = $this->createUserWithRole('super-admin');

        $response = $this->actingAs($superAdmin)->get(route('settings.legal'));

        $response->assertOk();
        $response->assertSee('Sin sincronización');
        $response->assertSee('Los indicadores todavía no están sincronizados');
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
