<?php

namespace Tests\Feature\Tenancy;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Core\Models\Tenant;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeDocument;
use Modules\Employees\Models\EmployeeLog;
use Modules\Payroll\Models\EmployeeItem;
use Modules\Payroll\Models\Item;
use Modules\Users\Models\User;
use Tests\TestCase;

class SubordinateModelsTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Company $companyA;

    protected Employee $employeeA;

    protected Tenant $tenantB;

    protected Company $companyB;

    protected Employee $employeeB;

    protected function setUp(): void
    {
        parent::setUp();

        // Empresa A
        $this->tenantA = Tenant::create(['name' => 'Empresa A', 'rut' => '76111111-1', 'slug' => 'empresa-a', 'status' => 'active']);
        $this->companyA = Company::create(['tenant_id' => $this->tenantA->id, 'name' => 'Empresa A', 'razon_social' => 'Empresa A SpA', 'rut' => '76111111-1', 'slug' => 'empresa-a']);
        $this->employeeA = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'company_id' => $this->companyA->id,
            'user_id' => User::factory()->create()->id,
            'first_name' => 'Trabajador',
            'last_name' => 'Empresa A',
            'rut' => '11.111.111-1',
            'email' => 'a@empresa.cl',
        ]);

        // Empresa B
        $this->tenantB = Tenant::create(['name' => 'Empresa B', 'rut' => '76222222-2', 'slug' => 'empresa-b', 'status' => 'active']);
        $this->companyB = Company::create(['tenant_id' => $this->tenantB->id, 'name' => 'Empresa B', 'razon_social' => 'Empresa B SpA', 'rut' => '76222222-2', 'slug' => 'empresa-b']);
        $this->employeeB = Employee::create([
            'tenant_id' => $this->tenantB->id,
            'company_id' => $this->companyB->id,
            'user_id' => User::factory()->create()->id,
            'first_name' => 'Trabajador',
            'last_name' => 'Empresa B',
            'rut' => '22.222.222-2',
            'email' => 'b@empresa.cl',
        ]);
    }

    public function test_attendance_records_are_scoped_by_tenant(): void
    {
        $context = app(TenantContext::class);

        // Crear registro en contexto de Empresa A
        $context->setCompanyId($this->companyA->id);
        $recordA = AttendanceRecord::create([
            'employee_id' => $this->employeeA->id,
            'date' => '2026-05-01',
            'status' => 'asistio',
        ]);
        $this->assertEquals($this->companyA->id, $recordA->company_id);
        $this->assertEquals($this->tenantA->id, $recordA->tenant_id);

        // Crear registro en contexto de Empresa B
        $context->setCompanyId($this->companyB->id);
        $recordB = AttendanceRecord::create([
            'employee_id' => $this->employeeB->id,
            'date' => '2026-05-01',
            'status' => 'asistio',
        ]);
        $this->assertEquals($this->companyB->id, $recordB->company_id);
        $this->assertEquals($this->tenantB->id, $recordB->tenant_id);

        // Consultar con contexto de Empresa A
        $context->setCompanyId($this->companyA->id);
        $this->assertEquals(1, AttendanceRecord::count());
        $this->assertEquals($recordA->id, AttendanceRecord::first()->id);
        $this->assertNull(AttendanceRecord::find($recordB->id)); // Bloqueado por Global Scope

        // Consultar con contexto de Empresa B
        $context->setCompanyId($this->companyB->id);
        $this->assertEquals(1, AttendanceRecord::count());
        $this->assertEquals($recordB->id, AttendanceRecord::first()->id);
        $this->assertNull(AttendanceRecord::find($recordA->id)); // Bloqueado por Global Scope
    }

    public function test_employee_documents_are_scoped_by_tenant(): void
    {
        $context = app(TenantContext::class);

        // Crear documento Empresa A
        $context->setCompanyId($this->companyA->id);
        $docA = EmployeeDocument::create([
            'employee_id' => $this->employeeA->id,
            'title' => 'Contrato A',
            'document_type' => 'contrato',
            'file_path' => 'docs/a.pdf',
        ]);

        // Crear documento Empresa B
        $context->setCompanyId($this->companyB->id);
        $docB = EmployeeDocument::create([
            'employee_id' => $this->employeeB->id,
            'title' => 'Contrato B',
            'document_type' => 'contrato',
            'file_path' => 'docs/b.pdf',
        ]);

        // Verificación aislamiento A
        $context->setCompanyId($this->companyA->id);
        $this->assertEquals(1, EmployeeDocument::count());
        $this->assertNotNull(EmployeeDocument::find($docA->id));
        $this->assertNull(EmployeeDocument::find($docB->id));

        // Verificación aislamiento B
        $context->setCompanyId($this->companyB->id);
        $this->assertEquals(1, EmployeeDocument::count());
        $this->assertNotNull(EmployeeDocument::find($docB->id));
        $this->assertNull(EmployeeDocument::find($docA->id));
    }

    public function test_employee_logs_are_scoped_by_tenant(): void
    {
        $context = app(TenantContext::class);

        // Crear log Empresa A
        $context->setCompanyId($this->companyA->id);
        $logA = EmployeeLog::create([
            'employee_id' => $this->employeeA->id,
            'type' => EmployeeLog::TYPE_AUDITORIA,
            'description' => 'Log Empresa A',
        ]);

        // Crear log Empresa B
        $context->setCompanyId($this->companyB->id);
        $logB = EmployeeLog::create([
            'employee_id' => $this->employeeB->id,
            'type' => EmployeeLog::TYPE_AUDITORIA,
            'description' => 'Log Empresa B',
        ]);

        // Verificación aislamiento: Empresa A tiene 2 logs (creación de colaborador + log manual) y 0 de Empresa B
        $context->setCompanyId($this->companyA->id);
        $this->assertEquals(2, EmployeeLog::count());
        $this->assertNull(EmployeeLog::find($logB->id));
        $this->assertTrue(EmployeeLog::all()->every(fn ($l) => (int) $l->company_id === (int) $this->companyA->id));

        // Empresa B tiene 2 logs (creación de colaborador + log manual) y 0 de Empresa A
        $context->setCompanyId($this->companyB->id);
        $this->assertEquals(2, EmployeeLog::count());
        $this->assertNull(EmployeeLog::find($logA->id));
        $this->assertTrue(EmployeeLog::all()->every(fn ($l) => (int) $l->company_id === (int) $this->companyB->id));
    }

    public function test_items_and_employee_items_are_scoped_by_tenant(): void
    {
        $context = app(TenantContext::class);

        // Crear item e item asignado en Empresa A
        $context->setCompanyId($this->companyA->id);
        $itemA = Item::create([
            'name' => 'Bono Empresa A',
            'type' => Item::TYPE_HABER_IMPONIBLE,
        ]);
        $empItemA = EmployeeItem::create([
            'employee_id' => $this->employeeA->id,
            'item_id' => $itemA->id,
            'amount' => 50000,
        ]);

        // Crear item e item asignado en Empresa B
        $context->setCompanyId($this->companyB->id);
        $itemB = Item::create([
            'name' => 'Bono Empresa B',
            'type' => Item::TYPE_HABER_IMPONIBLE,
        ]);
        $empItemB = EmployeeItem::create([
            'employee_id' => $this->employeeB->id,
            'item_id' => $itemB->id,
            'amount' => 70000,
        ]);

        // Verificación en Empresa A
        $context->setCompanyId($this->companyA->id);
        $this->assertEquals(1, Item::count());
        $this->assertEquals($itemA->id, Item::first()->id);
        $this->assertNull(Item::find($itemB->id));

        $this->assertEquals(1, EmployeeItem::count());
        $this->assertEquals($empItemA->id, EmployeeItem::first()->id);
        $this->assertNull(EmployeeItem::find($empItemB->id));

        // Verificación en Empresa B
        $context->setCompanyId($this->companyB->id);
        $this->assertEquals(1, Item::count());
        $this->assertEquals($itemB->id, Item::first()->id);
        $this->assertNull(Item::find($itemA->id));

        $this->assertEquals(1, EmployeeItem::count());
        $this->assertEquals($empItemB->id, EmployeeItem::first()->id);
        $this->assertNull(EmployeeItem::find($empItemA->id));
    }
}
