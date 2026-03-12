<?php

namespace Tests\Unit\Tenancy;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CostCenter;
use Modules\Employees\Models\Employee;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();

        parent::tearDown();
    }

    public function test_company_owned_models_are_filtered_by_tenant_context(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'razon_social' => 'Empresa A SpA',
            'rut' => '11111111-1',
        ]);

        $companyB = Company::create([
            'name' => 'Empresa B',
            'razon_social' => 'Empresa B SpA',
            'rut' => '22222222-2',
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Employee::create([
            'user_id' => $userA->id,
            'company_id' => $companyA->id,
        ]);

        Employee::create([
            'user_id' => $userB->id,
            'company_id' => $companyB->id,
        ]);

        app(TenantContext::class)->setCompanyId($companyA->id);

        $visibleCompanyIds = Employee::query()->pluck('company_id')->all();

        $this->assertSame([$companyA->id], $visibleCompanyIds);
    }

    public function test_company_id_is_auto_assigned_when_creating_scoped_models(): void
    {
        $company = Company::create([
            'name' => 'Empresa Tenant',
            'razon_social' => 'Empresa Tenant SpA',
            'rut' => '33333333-3',
        ]);

        app(TenantContext::class)->setCompanyId($company->id);

        $costCenter = CostCenter::create([
            'code' => 'ADM',
            'name' => 'Administración',
            'status' => 'active',
        ]);

        $this->assertSame($company->id, $costCenter->company_id);
    }

    public function test_super_admin_initializes_bypass_mode(): void
    {
        $role = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'company_id' => null,
        ]);

        $user->assignRole($role);

        $tenantContext = app(TenantContext::class);
        $tenantContext->initializeForUser($user);

        $this->assertTrue($tenantContext->isBypassed());
        $this->assertNull($tenantContext->companyId());
    }
}
