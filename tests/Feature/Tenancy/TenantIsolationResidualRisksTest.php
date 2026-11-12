<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CostCenter;
use Modules\Core\Models\Tenant;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeDocument;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantIsolationResidualRisksTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Company $companyA;

    protected Employee $employeeA;

    protected Tenant $tenantB;

    protected Company $companyB;

    protected Employee $employeeB;

    protected Tenant $tenantC;

    protected Company $companyC;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super-admin', 'admin', 'contador', 'recursos-humanos', 'employee', 'multi-company'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Empresa A
        $this->tenantA = Tenant::create(['name' => 'Empresa A', 'rut' => '76111111-1', 'slug' => 'empresa-a', 'status' => 'active']);
        $this->companyA = Company::create(['tenant_id' => $this->tenantA->id, 'name' => 'Empresa A', 'razon_social' => 'Empresa A SpA', 'rut' => '76111111-1', 'slug' => 'empresa-a']);
        $this->employeeA = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'company_id' => $this->companyA->id,
            'user_id' => User::factory()->create()->id,
            'first_name' => 'Empleado',
            'last_name' => 'Uno',
            'rut' => '11111111-1',
            'email' => 'uno@empresa-a.cl',
            'status' => 'active',
        ]);

        // Empresa B
        $this->tenantB = Tenant::create(['name' => 'Empresa B', 'rut' => '76222222-2', 'slug' => 'empresa-b', 'status' => 'active']);
        $this->companyB = Company::create(['tenant_id' => $this->tenantB->id, 'name' => 'Empresa B', 'razon_social' => 'Empresa B SpA', 'rut' => '76222222-2', 'slug' => 'empresa-b']);
        $this->employeeB = Employee::create([
            'tenant_id' => $this->tenantB->id,
            'company_id' => $this->companyB->id,
            'user_id' => User::factory()->create()->id,
            'first_name' => 'Empleado',
            'last_name' => 'Dos',
            'rut' => '22222222-2',
            'email' => 'dos@empresa-b.cl',
            'status' => 'active',
        ]);

        // Empresa C
        $this->tenantC = Tenant::create(['name' => 'Empresa C', 'rut' => '76333333-3', 'slug' => 'empresa-c', 'status' => 'active']);
        $this->companyC = Company::create(['tenant_id' => $this->tenantC->id, 'name' => 'Empresa C', 'razon_social' => 'Empresa C SpA', 'rut' => '76333333-3', 'slug' => 'empresa-c']);
    }

    /**
     * 1. Prioridad 1: TenantContext::initializeForUser() no debe aceptar selected_company_id
     *    sin comprobar una autorización válida, incluso si el usuario no tiene membresías.
     */
    public function test_tenant_context_rejects_selected_company_for_user_without_any_companies_or_memberships(): void
    {
        $orphanUser = User::factory()->create(); // Usuario sin empresas, sin membresías, sin employee
        session(['selected_company_id' => $this->companyA->id, 'selected_tenant_id' => $this->tenantA->id]);

        $context = app(TenantContext::class);
        $context->initializeForUser($orphanUser);

        $this->assertNull($context->companyId());
        $this->assertNull($context->tenantId());
        $this->assertFalse($context->hasTenant());
        $this->assertNull(session('selected_company_id'));
        $this->assertNull(session('selected_tenant_id'));
    }

    public function test_tenant_context_rejects_unauthorized_company_for_user_with_single_membership_and_falls_back(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->companies()->attach($this->companyA->id, ['role' => 'admin']);

        // Intento de forjar la sesión hacia Empresa B (no autorizada)
        session(['selected_company_id' => $this->companyB->id, 'selected_tenant_id' => $this->tenantB->id]);

        $context = app(TenantContext::class);
        $context->initializeForUser($user);

        // Se limpia la sesión inválida y se selecciona Empresa A como único fallback autorizado
        $this->assertSame($this->companyA->id, $context->companyId());
        $this->assertSame($this->tenantA->id, $context->tenantId());
        $this->assertNull(session('selected_company_id'));
    }

    public function test_tenant_context_rejects_unauthorized_company_for_user_with_multiple_memberships_without_fallback(): void
    {
        $user = User::factory()->create();
        $user->assignRole('contador');
        $user->companies()->attach($this->companyA->id, ['role' => 'contador']);
        $user->companies()->attach($this->companyB->id, ['role' => 'contador']);

        // Intento de forjar la sesión hacia Empresa C (no autorizada)
        session(['selected_company_id' => $this->companyC->id, 'selected_tenant_id' => $this->tenantC->id]);

        $context = app(TenantContext::class);
        $context->initializeForUser($user);

        // Al tener múltiples empresas autorizadas pero ninguna seleccionada válidamente,
        // no debe asumir ninguna y la sesión debe ser purgada.
        $this->assertNull($context->companyId());
        $this->assertNull($context->tenantId());
        $this->assertFalse($context->hasTenant());
        $this->assertNull(session('selected_company_id'));
    }

    /**
     * 2. Prioridad 2: ResolveTenantContext no debe permitir que un usuario anónimo
     *    establezca contexto para acceder a recursos privados.
     */
    public function test_resolve_tenant_context_blocks_anonymous_user_on_company_route_and_does_not_set_context(): void
    {
        $context = app(TenantContext::class);
        $this->assertFalse($context->hasTenant());

        // Petición anónima JSON a recurso administrativo con parámetro de ruta {company}
        $jsonResponse = $this->getJson(route('companies.dashboard', ['company' => $this->companyA->slug]));
        $jsonResponse->assertUnauthorized();
        $this->assertFalse($context->hasTenant());
        $this->assertNull($context->companyId());

        // Petición anónima web tradicional redirige a login sin establecer contexto
        $webResponse = $this->get(route('companies.dashboard', ['company' => $this->companyA->slug]));
        $webResponse->assertRedirect(route('login'));
        $this->assertFalse($context->hasTenant());
        $this->assertNull($context->companyId());
    }

    public function test_resolve_tenant_context_ignores_manipulated_session_for_anonymous_user(): void
    {
        $context = app(TenantContext::class);

        // Simular petición anónima con sesión preestablecida
        $this->withSession(['selected_company_id' => $this->companyA->id])
            ->get('/login')
            ->assertOk();

        $this->assertFalse($context->hasTenant());
        $this->assertNull($context->companyId());
    }

    /**
     * 3. Prioridad 3: Controladores administrativos diferenciando acceso de lectura y permisos de escritura.
     */
    public function test_read_only_user_cannot_perform_write_operations_on_company(): void
    {
        $userReadOnly = User::factory()->create();
        $userReadOnly->companies()->attach($this->companyA->id, [
            'role' => 'contador',
            'permissions' => json_encode(['payroll.view']), // Solo lectura de nómina
        ]);

        // Puede ver el dashboard y períodos de nómina
        $this->actingAs($userReadOnly)
            ->get(route('companies.dashboard', $this->companyA))
            ->assertOk();

        $this->actingAs($userReadOnly)
            ->get(route('companies.payroll-periods.index', $this->companyA))
            ->assertOk();

        // NO puede actualizar datos de la empresa (requiere company.manage)
        $this->actingAs($userReadOnly)
            ->put(route('companies.update', $this->companyA), [
                'name' => 'Hackeado',
                'payment_frequency' => 'mensual',
                'gratification_system' => 'art_50',
                'gratification_months' => 12,
            ])
            ->assertForbidden();

        // NO puede crear centros de costo (requiere company.manage)
        $this->actingAs($userReadOnly)
            ->post(route('companies.cost-centers.store', $this->companyA), [
                'code' => 'CC-99',
                'name' => 'Centro No Autorizado',
                'status' => 'active',
            ])
            ->assertForbidden();

        // NO puede crear ni actualizar colaboradores (requiere employees.manage)
        $this->actingAs($userReadOnly)
            ->post(route('companies.employees.store', $this->companyA), [
                'first_name' => 'Colaborador',
                'last_name' => 'Ilegal',
                'email' => 'ilegal@test.cl',
                'password' => 'secret123',
            ])
            ->assertForbidden();

        // NO puede gestionar usuarios (requiere users.manage)
        $this->actingAs($userReadOnly)
            ->post(route('companies.users.store', $this->companyA), [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@test.cl',
                'password' => 'password',
                'role' => 'admin',
            ])
            ->assertForbidden();

        // NO puede calcular nómina (requiere payroll.calculate)
        $period = PayrollPeriod::create([
            'tenant_id' => $this->tenantA->id,
            'company_id' => $this->companyA->id,
            'name' => 'Periodo Prueba',
            'year' => 2026,
            'month' => 6,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
        ]);

        $this->actingAs($userReadOnly)
            ->post(route('companies.payroll-periods.calculate', [$this->companyA, $period]))
            ->assertForbidden();

        // NO puede eliminar la empresa (exclusivo de super-admin)
        $this->actingAs($userReadOnly)
            ->delete(route('companies.destroy', $this->companyA->id))
            ->assertForbidden();
    }

    public function test_admin_cannot_delete_company_only_super_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->companies()->attach($this->companyA->id, [
            'role' => 'admin',
            'permissions' => json_encode(['company.manage', 'payroll.calculate', 'payroll.view', 'employees.manage', 'users.manage']),
        ]);

        $this->actingAs($admin)
            ->delete(route('companies.destroy', $this->companyA->id))
            ->assertForbidden();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)
            ->delete(route('companies.destroy', $this->companyA->id))
            ->assertRedirect(route('companies.index'));

        $this->assertDatabaseMissing('companies', ['id' => $this->companyA->id]);
    }

    /**
     * 4. Colaboradores sin privilegios administrativos (rol 'employee').
     */
    public function test_regular_employee_cannot_access_or_manipulate_any_administrative_routes(): void
    {
        $employeeUser = $this->employeeA->user;
        $employeeUser->syncRoles(['employee']);

        // Petición a dashboard
        $this->actingAs($employeeUser)
            ->get(route('companies.dashboard', $this->companyA))
            ->assertForbidden();

        // Petición a edición de empresa
        $this->actingAs($employeeUser)
            ->get(route('companies.edit', $this->companyA))
            ->assertForbidden();

        // Petición de escritura forjada a edición de empresa
        $this->actingAs($employeeUser)
            ->put(route('companies.update', $this->companyA), [
                'name' => 'Mutación no permitida',
                'payment_frequency' => 'mensual',
                'gratification_system' => 'art_50',
                'gratification_months' => 12,
            ])
            ->assertForbidden();

        // Manipulación de sesión para forzar selected_company_id
        session(['selected_company_id' => $this->companyA->id]);
        $context = app(TenantContext::class);
        $context->initializeForUser($employeeUser);

        // Para rol únicamente 'employee', initializeForUser purga la sesión y no inicializa contexto administrativo
        $this->assertNull($context->companyId());
        $this->assertNull(session('selected_company_id'));
    }

    /**
     * 5. Outsourcing con múltiples tenants.
     */
    public function test_outsourcing_accountant_isolated_between_managed_and_unmanaged_tenants(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('contador');

        // Tenant A: Permiso completo de nómina (calculate + view)
        $accountant->companies()->attach($this->companyA->id, [
            'role' => 'contador',
            'is_external' => true,
            'permissions' => json_encode(['payroll.view', 'payroll.calculate']),
        ]);

        // Tenant B: Solo lectura (view)
        $accountant->companies()->attach($this->companyB->id, [
            'role' => 'contador',
            'is_external' => true,
            'permissions' => json_encode(['payroll.view']),
        ]);

        // 1. Acceso a Tenant A
        $this->actingAs($accountant)
            ->get(route('companies.dashboard', $this->companyA))
            ->assertOk();

        $this->actingAs($accountant)
            ->post(route('companies.payroll-periods.store', $this->companyA), [
                'year' => 2026,
                'month' => 7,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
            ])
            ->assertRedirect();

        // 2. Acceso a Tenant B: lectura permitida, creación de período denegada
        $this->actingAs($accountant)
            ->get(route('companies.dashboard', $this->companyB))
            ->assertOk();

        $this->actingAs($accountant)
            ->post(route('companies.payroll-periods.store', $this->companyB), [
                'year' => 2026,
                'month' => 7,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
            ])
            ->assertForbidden();

        // 3. Acceso a Tenant C (no contratado): totalmente bloqueado
        $this->actingAs($accountant)
            ->get(route('companies.dashboard', $this->companyC))
            ->assertForbidden();

        $this->actingAs($accountant)
            ->post(route('companies.payroll-periods.store', $this->companyC), [
                'year' => 2026,
                'month' => 7,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-31',
            ])
            ->assertForbidden();
    }

    /**
     * 6. Prioridad 4: Modelos subordinados en lecturas, creaciones y actualizaciones cross-tenant.
     */
    public function test_subordinate_models_strictly_reject_cross_tenant_creation(): void
    {
        $context = app(TenantContext::class);
        $context->setCompanyId($this->companyA->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se puede asociar el modelo a un tenant distinto al activo.');

        // Intentar crear un registro subordinado indicando explícitamente company_id de Empresa B mientras contexto es Empresa A
        CostCenter::create([
            'tenant_id' => $this->tenantB->id,
            'company_id' => $this->companyB->id,
            'code' => 'ILLEGAL-CC',
            'name' => 'Centro Ilegal',
            'status' => 'active',
        ]);
    }

    public function test_subordinate_models_strictly_reject_cross_tenant_update(): void
    {
        $context = app(TenantContext::class);

        // Crear registro en contexto de Empresa A
        $context->setCompanyId($this->companyA->id);
        $costCenterA = CostCenter::create([
            'code' => 'CC-A',
            'name' => 'Centro Empresa A',
            'status' => 'active',
        ]);

        // Cambiar el contexto a Empresa B
        $context->setCompanyId($this->companyB->id);

        // Intentar actualizar la instancia de Empresa A estando en contexto Empresa B debe fallar
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se puede actualizar un modelo perteneciente a otro tenant.');

        $costCenterA->name = 'Nombre Modificado en Contexto B';
        $costCenterA->save();
    }

    public function test_subordinate_models_reject_reassigning_tenant_or_company(): void
    {
        $context = app(TenantContext::class);
        $context->setCompanyId($this->companyA->id);

        $docA = EmployeeDocument::create([
            'employee_id' => $this->employeeA->id,
            'title' => 'Contrato A',
            'document_type' => 'contrato',
            'file_path' => 'docs/a.pdf',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se puede reasignar el modelo a otro tenant.');

        // Intentar reasignar el documento a otra empresa
        $docA->company_id = $this->companyB->id;
        $docA->save();
    }

    public function test_subordinate_models_reject_creation_and_update_without_active_tenant_context(): void
    {
        $context = app(TenantContext::class);
        $context->clear(); // Sin tenant, no bypassed

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se puede crear el modelo sin un tenant activo.');

        CostCenter::create([
            'code' => 'ORPHAN',
            'name' => 'Huérfano',
            'status' => 'active',
        ]);
    }
}
