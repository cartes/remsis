<?php

declare(strict_types=1);

namespace Tests\Feature\Employees;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Core\Database\Seeders\LreConceptsSeeder;
use Modules\Core\Models\LreConcept;
use Modules\Core\Models\Tenant;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmploymentContract;
use Modules\Payroll\Models\PayrollItem;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmploymentContractAndLreItemsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();

        parent::tearDown();
    }

    public function test_creating_and_updating_employee_automatically_syncs_active_employment_contract(): void
    {
        app(TenantContext::class)->bypass();

        $tenant = Tenant::create(['name' => 'Grupo Sur', 'slug' => 'grupo-sur']);
        $company = Company::create([
            'tenant_id' => $tenant->id,
            'name' => 'Constructora Sur SpA',
            'rut' => '76555444-3',
            'weekly_hours' => 42,
            'gratification_system' => 'art_50',
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'first_name' => 'Camila',
            'last_name' => 'Rojas',
            'rut' => '18123456-7',
            'position' => 'Ingeniera de Obra',
            'hire_date' => '2026-03-01',
            'contract_type' => 'indefinido',
            'work_schedule_type' => 'full_time',
            'salary' => 1500000,
            'salary_type' => 'mensual',
            'status' => 'active',
        ]);

        $contract = $employee->activeContract()->first();

        $this->assertNotNull($contract);
        $this->assertSame($tenant->id, $contract->tenant_id);
        $this->assertSame($company->id, $contract->company_id);
        $this->assertSame(1500000.0, $contract->base_salary);
        $this->assertSame(42, $contract->weekly_hours);
        $this->assertTrue($contract->isIndefinite());
        $this->assertTrue($contract->isActive());

        // Factor hora extra para 42 hrs semanales con recargo 50%: ((1/30)*7/42)*1.5 = 0.008333
        $this->assertSame(0.008333, $contract->overtimeHourlyFactor());

        // Al actualizar sueldo y tipo de contrato del colaborador, se actualiza su contrato activo
        $employee->update([
            'salary' => 1800000,
            'contract_type' => 'plazo_fijo',
        ]);

        $contract->refresh();
        $this->assertSame(1800000.0, $contract->base_salary);
        $this->assertTrue($contract->isFixedTerm());
    }

    public function test_payroll_item_linked_to_lre_concept_inherits_labor_code_flags(): void
    {
        $this->seed(LreConceptsSeeder::class);
        app(TenantContext::class)->bypass();

        $company = Company::create([
            'name' => 'Retail Austral Ltda',
            'rut' => '76999888-7',
        ]);

        $mealLre = LreConcept::query()->where('code', '2101')->firstOrFail();

        $item = PayrollItem::create([
            'company_id' => $company->id,
            'lre_concept_id' => $mealLre->id,
            'name' => 'Colación Mensual',
            'code' => 'COL_MENSUAL',
            'type' => PayrollItem::TYPE_HABER_NO_IMPONIBLE,
            'is_taxable' => true, // Será corregido por el hook normativo de LRE 2101 (Art. 41 inc. 2 CT)
        ]);

        $this->assertFalse($item->is_taxable);
        $this->assertFalse($item->is_tributable);
        $this->assertFalse($item->is_taxable_afc);
        $this->assertSame('2101', $item->lreConcept->code);
    }

    public function test_company_items_controller_can_seed_official_lre_template(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        $company = Company::create([
            'name' => 'Servicios Mineros SpA',
            'rut' => '76444333-2',
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('companies.items.seed_lre', $company));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertGreaterThanOrEqual(7, PayrollItem::forCompany($company->id)->whereNotNull('lre_concept_id')->count());
    }
}

