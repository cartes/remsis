<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OutsourcingAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super-admin', 'admin', 'contador', 'recursos-humanos', 'employee', 'multi-company'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    public function test_personal_access_is_separated_from_administrative_permissions(): void
    {
        $company = Company::create([
            'name' => 'Constructora Austral SpA',
            'razon_social' => 'Constructora Austral SpA',
            'rut' => '76111111-1',
        ]);

        // 1. Colaborador regular (solo rol 'employee')
        $regularEmployeeUser = User::factory()->create(['company_id' => $company->id]);
        $regularEmployeeUser->assignRole('employee');
        Employee::create([
            'user_id' => $regularEmployeeUser->id,
            'company_id' => $company->id,
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'rut' => '15111111-1',
            'email' => $regularEmployeeUser->email,
            'status' => 'active',
        ]);

        // Puede acceder a su portal personal ("Mi Remsys")
        $this->actingAs($regularEmployeeUser)
            ->get(route('employee.profile.show'))
            ->assertOk();

        // NO puede acceder a las pantallas administrativas de la empresa
        $this->actingAs($regularEmployeeUser)
            ->get(route('companies.dashboard', $company))
            ->assertForbidden();

        $this->actingAs($regularEmployeeUser)
            ->get(route('companies.edit', $company))
            ->assertForbidden();

        $this->actingAs($regularEmployeeUser)
            ->get(route('companies.employees', $company))
            ->assertForbidden();

        $this->actingAs($regularEmployeeUser)
            ->get(route('companies.users.index', $company))
            ->assertForbidden();

        // 2. Colaborador interno con funciones administrativas (Employee + rol 'contador')
        $internalAccountant = User::factory()->create(['company_id' => $company->id]);
        $internalAccountant->assignRole('contador');
        Employee::create([
            'user_id' => $internalAccountant->id,
            'company_id' => $company->id,
            'first_name' => 'Daniela',
            'last_name' => 'Soto',
            'rut' => '16222222-2',
            'email' => $internalAccountant->email,
            'status' => 'active',
        ]);

        // Conserva acceso a su portal personal aunque su rol sea 'contador' y no 'employee'
        $this->actingAs($internalAccountant)
            ->get(route('employee.profile.show'))
            ->assertOk();

        // Y también tiene acceso administrativo a su empresa
        $this->actingAs($internalAccountant)
            ->get(route('companies.dashboard', $company))
            ->assertOk();

        $this->actingAs($internalAccountant)
            ->get(route('companies.edit', ['company' => $company, 'section' => 'company-data']))
            ->assertOk()
            ->assertViewIs('companies::edit');
    }

    public function test_external_accountant_can_manage_multiple_tenants_without_fake_employee_profile_or_access_to_foreign_companies(): void
    {
        $companyA = Company::create(['name' => 'Cliente A SpA', 'razon_social' => 'Cliente A SpA', 'rut' => '76111111-1']);
        $companyB = Company::create(['name' => 'Cliente B SpA', 'razon_social' => 'Cliente B SpA', 'rut' => '76222222-2']);
        $companyC = Company::create(['name' => 'Empresa Ajena SpA', 'razon_social' => 'Empresa Ajena SpA', 'rut' => '76333333-3']);

        $externalAccountant = User::factory()->create();
        $externalAccountant->assignRole(['contador', 'multi-company']);
        $externalAccountant->companies()->attach([
            $companyA->id => ['role' => 'contador', 'is_external' => true],
            $companyB->id => ['role' => 'contador', 'is_external' => true],
        ]);

        // No tiene vínculo laboral (Employee)
        $this->assertFalse($externalAccountant->hasPersonalPortalAccess());
        $this->actingAs($externalAccountant)
            ->get(route('employee.profile.show'))
            ->assertForbidden();

        // Puede operar en Cliente A y Cliente B
        $this->actingAs($externalAccountant)
            ->get(route('companies.dashboard', $companyA))
            ->assertOk();

        $this->actingAs($externalAccountant)
            ->get(route('companies.dashboard', $companyB))
            ->assertOk();

        // No puede acceder a Empresa Ajena C
        $this->actingAs($externalAccountant)
            ->get(route('companies.dashboard', $companyC))
            ->assertForbidden();

        $this->actingAs($externalAccountant)
            ->post(route('companies.selected', $companyC))
            ->assertForbidden();
    }

    public function test_external_accountant_can_delegate_functions_in_managed_tenant_but_not_in_foreign_tenant(): void
    {
        $companyA = Company::create(['name' => 'Cliente A SpA', 'razon_social' => 'Cliente A SpA', 'rut' => '76111111-1']);
        $companyB = Company::create(['name' => 'Cliente B SpA', 'razon_social' => 'Cliente B SpA', 'rut' => '76222222-2']);
        $companyForeign = Company::create(['name' => 'Ajena SpA', 'razon_social' => 'Ajena SpA', 'rut' => '76333333-3']);

        $externalAccountant = User::factory()->create();
        $externalAccountant->assignRole('contador');
        $externalAccountant->companies()->attach([
            $companyA->id => ['role' => 'contador', 'is_external' => true],
            $companyB->id => ['role' => 'contador', 'is_external' => true],
        ]);

        // 1. El contador externo delega a un operador de su equipo acceso parcial en Company A
        // (solo ver y calcular remuneraciones, sin aprobar nómina ni gestionar usuarios, y fuera de nómina)
        $response = $this->actingAs($externalAccountant)->post(route('companies.users.store', $companyA), [
            'name' => 'Operador Nómina',
            'email' => 'operador@estudio.cl',
            'password' => 'secret123',
            'role' => 'contador',
            'is_in_payroll' => false,
            'permissions' => ['payroll.view', 'payroll.calculate'],
        ]);

        $response->assertRedirect();

        $operator = User::where('email', 'operador@estudio.cl')->firstOrFail();

        // No debe habérsele creado un Employee ficticio en Company A
        $this->assertDatabaseMissing('employees', [
            'user_id' => $operator->id,
            'company_id' => $companyA->id,
        ]);

        // Verificar permisos delegados del operador en Company A
        $this->assertTrue($operator->canAccessCompany($companyA));
        $this->assertTrue($operator->hasCompanyPermission($companyA, 'payroll.view'));
        $this->assertTrue($operator->hasCompanyPermission($companyA, 'payroll.calculate'));
        $this->assertFalse($operator->hasCompanyPermission($companyA, 'payroll.approve'));
        $this->assertFalse($operator->hasCompanyPermission($companyA, 'users.manage'));

        // El operador NO hereda acceso automático a Company B (otro cliente del mismo estudio) ni a CompanyForeign
        $this->assertFalse($operator->canAccessCompany($companyB));
        $this->assertFalse($operator->canAccessCompany($companyForeign));

        // El operador no puede cerrar/aprobar período en Company A ni crear otros usuarios
        $period = PayrollPeriod::create([
            'company_id' => $companyA->id,
            'year' => 2026,
            'month' => 11,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => PayrollPeriod::STATUS_OPEN,
        ]);

        $this->actingAs($operator)
            ->postJson(route('companies.payroll-periods.update-status', [$companyA, $period]), [
                'status' => PayrollPeriod::STATUS_CLOSED,
            ])
            ->assertForbidden();

        $this->actingAs($operator)
            ->post(route('companies.users.store', $companyA), [
                'name' => 'Intruso',
                'email' => 'intruso@estudio.cl',
                'password' => 'secret123',
                'role' => 'admin',
            ])
            ->assertForbidden();

        // 2. El contador externo intenta crear o vincular un usuario en una empresa ajena (CompanyForeign) -> 403
        $this->actingAs($externalAccountant)
            ->post(route('companies.users.store', $companyForeign), [
                'name' => 'No Autorizado',
                'email' => 'noautorizado@estudio.cl',
                'password' => 'secret123',
                'role' => 'contador',
            ])
            ->assertForbidden();

        $this->actingAs($externalAccountant)
            ->postJson(route('users.attach-company', $operator), [
                'company_id' => $companyForeign->id,
            ])
            ->assertForbidden();
    }
}

