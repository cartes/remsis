<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Companies\Models\Company;
use Modules\Core\Models\Tenant;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResolveTenantContextSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'contador', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    }

    public function test_user_with_no_companies_is_forbidden_from_accessing_any_company_route(): void
    {
        $tenant = Tenant::create(['name' => 'Empresa A', 'rut' => '76111111-1', 'slug' => 'empresa-a', 'status' => 'active']);
        $company = Company::create(['tenant_id' => $tenant->id, 'name' => 'Empresa A', 'razon_social' => 'Empresa A SpA', 'rut' => '76111111-1', 'slug' => 'empresa-a']);

        $userWithoutCompanies = User::factory()->create();

        $response = $this->actingAs($userWithoutCompanies)
            ->get(route('companies.employees', ['company' => $company->slug]));

        $response->assertStatus(403);
    }

    public function test_user_from_company_a_cannot_access_company_b(): void
    {
        $tenantA = Tenant::create(['name' => 'Empresa A', 'rut' => '76111111-1', 'slug' => 'empresa-a', 'status' => 'active']);
        $companyA = Company::create(['tenant_id' => $tenantA->id, 'name' => 'Empresa A', 'razon_social' => 'Empresa A SpA', 'rut' => '76111111-1', 'slug' => 'empresa-a']);

        $tenantB = Tenant::create(['name' => 'Empresa B', 'rut' => '76222222-2', 'slug' => 'empresa-b', 'status' => 'active']);
        $companyB = Company::create(['tenant_id' => $tenantB->id, 'name' => 'Empresa B', 'razon_social' => 'Empresa B SpA', 'rut' => '76222222-2', 'slug' => 'empresa-b']);

        $userA = User::factory()->create();
        $userA->assignRole('admin');
        $userA->companies()->attach($companyA->id, ['role' => 'admin']);

        // Acceso a su propia empresa -> Permitido
        $responseA = $this->actingAs($userA)
            ->get(route('companies.employees', ['company' => $companyA->slug]));
        $responseA->assertSuccessful();

        // Intento de acceso a Empresa B -> Prohibido (403)
        $responseB = $this->actingAs($userA)
            ->get(route('companies.employees', ['company' => $companyB->slug]));
        $responseB->assertStatus(403);
    }

    public function test_external_accountant_can_access_authorized_companies_and_is_blocked_on_unauthorized(): void
    {
        $tenant1 = Tenant::create(['name' => 'Cliente 1', 'rut' => '76111111-1', 'slug' => 'cliente-1', 'status' => 'active']);
        $company1 = Company::create(['tenant_id' => $tenant1->id, 'name' => 'Cliente 1', 'razon_social' => 'Cliente 1 SpA', 'rut' => '76111111-1', 'slug' => 'cliente-1']);

        $tenant2 = Tenant::create(['name' => 'Cliente 2', 'rut' => '76222222-2', 'slug' => 'cliente-2', 'status' => 'active']);
        $company2 = Company::create(['tenant_id' => $tenant2->id, 'name' => 'Cliente 2', 'razon_social' => 'Cliente 2 SpA', 'rut' => '76222222-2', 'slug' => 'cliente-2']);

        $tenant3 = Tenant::create(['name' => 'Cliente 3', 'rut' => '76333333-3', 'slug' => 'cliente-3', 'status' => 'active']);
        $company3 = Company::create(['tenant_id' => $tenant3->id, 'name' => 'Cliente 3', 'razon_social' => 'Cliente 3 SpA', 'rut' => '76333333-3', 'slug' => 'cliente-3']);

        $accountant = User::factory()->create();
        $accountant->assignRole('contador');

        // Asignado como outsourcing únicamente a Cliente 1 y Cliente 2
        $accountant->companies()->attach($company1->id, ['role' => 'contador', 'is_external' => true]);
        $accountant->companies()->attach($company2->id, ['role' => 'contador', 'is_external' => true]);

        // Puede acceder a Cliente 1
        $this->actingAs($accountant)
            ->get(route('companies.employees', ['company' => $company1->slug]))
            ->assertSuccessful();

        // Puede acceder a Cliente 2
        $this->actingAs($accountant)
            ->get(route('companies.employees', ['company' => $company2->slug]))
            ->assertSuccessful();

        // Bloqueado en Cliente 3 (403)
        $this->actingAs($accountant)
            ->get(route('companies.employees', ['company' => $company3->slug]))
            ->assertStatus(403);
    }

    public function test_super_admin_can_access_any_company(): void
    {
        $tenant = Tenant::create(['name' => 'Empresa X', 'rut' => '76999999-9', 'slug' => 'empresa-x', 'status' => 'active']);
        $company = Company::create(['tenant_id' => $tenant->id, 'name' => 'Empresa X', 'razon_social' => 'Empresa X SpA', 'rut' => '76999999-9', 'slug' => 'empresa-x']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)
            ->get(route('companies.employees', ['company' => $company->slug]));

        $response->assertSuccessful();
    }

    public function test_company_policy_evaluates_permissions_consistently(): void
    {
        $tenant = Tenant::create(['name' => 'Empresa Test', 'rut' => '76555555-5', 'slug' => 'empresa-test', 'status' => 'active']);
        $company = Company::create(['tenant_id' => $tenant->id, 'name' => 'Empresa Test', 'razon_social' => 'Empresa Test SpA', 'rut' => '76555555-5', 'slug' => 'empresa-test']);

        $admin = User::factory()->create();
        $admin->companies()->attach($company->id, [
            'role' => 'admin',
            'permissions' => json_encode(['company.manage', 'payroll.calculate', 'payroll.view', 'employees.manage']),
        ]);

        $restrictedUser = User::factory()->create();
        $restrictedUser->companies()->attach($company->id, [
            'role' => 'contador',
            'permissions' => json_encode(['payroll.view']), // Solo ver nómina, no calcular ni editar empresa
        ]);

        $unrelatedUser = User::factory()->create();

        // Super-admin
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $company));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $company));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('calculatePayroll', $company));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('delete', $company));

        // Admin
        $this->assertTrue(Gate::forUser($admin)->allows('view', $company));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $company));
        $this->assertTrue(Gate::forUser($admin)->allows('calculatePayroll', $company));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $company)); // Delete es exclusivo de super-admin

        // Restricted User
        $this->assertTrue(Gate::forUser($restrictedUser)->allows('view', $company));
        $this->assertTrue(Gate::forUser($restrictedUser)->allows('viewPayroll', $company));
        $this->assertFalse(Gate::forUser($restrictedUser)->allows('calculatePayroll', $company));
        $this->assertFalse(Gate::forUser($restrictedUser)->allows('update', $company));

        // Unrelated User
        $this->assertFalse(Gate::forUser($unrelatedUser)->allows('view', $company));
        $this->assertFalse(Gate::forUser($unrelatedUser)->allows('update', $company));
        $this->assertFalse(Gate::forUser($unrelatedUser)->allows('calculatePayroll', $company));
    }
}
