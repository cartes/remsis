<?php

namespace Tests\Feature\Payroll;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AdminPanel\Models\Afp;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\EmployeeItem;
use Modules\Payroll\Models\Item;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Services\PayslipService;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\Support\CreatesMonthlyIndicator;
use Tests\TestCase;

/**
 * Liquidación de sueldo en PDF y su publicación en el portal del colaborador.
 */
class PayslipTest extends TestCase
{
    use CreatesMonthlyIndicator;
    use RefreshDatabase;

    private User $actor;

    private Company $company;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createMonthlyIndicator(2026, 3);

        $this->company = Company::create([
            'name' => 'Empresa Liquidaciones',
            'razon_social' => 'Empresa Liquidaciones SpA',
            'rut' => '55555555-5',
            'gratification_system' => Company::GRATIFICATION_SYSTEM_NONE,
            'weekly_hours' => 42,
        ]);

        app(TenantContext::class)->bypass();
        $this->actor = User::factory()->create(['company_id' => $this->company->id]);
        $this->period = PayrollPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2026,
            'month' => 3,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);
        app(TenantContext::class)->bypass(false);

        $this->actAsAdmin();
    }

    public function test_payslip_groups_the_calculated_concepts_and_matches_the_totals(): void
    {
        $afp = Afp::create(['nombre' => 'HABITAT', 'rate' => 10.0, 'commission' => 1.27]);
        $employee = $this->employee(['salary' => 2000000, 'afp_id' => $afp->id, 'contract_type' => 'indefinido']);
        $this->assign($employee, Item::TYPE_HABER_NO_IMPONIBLE, 'Colación', 60000);
        $this->assign($employee, Item::TYPE_CREDITO, 'Préstamo empresa', 50000, ['total_installments' => 6]);

        $line = $this->calculate($employee);
        $data = app(PayslipService::class)->data($line);

        $this->assertSame(
            ['Sueldo base (30 días)'],
            $data['sections']['taxable']->pluck('label')->all()
        );
        $this->assertSame(['Colación'], $data['sections']['non_taxable']->pluck('label')->all());
        $this->assertSame(
            [225400, 140000, 12000, 27104],
            $data['sections']['legal']->pluck('amount')->all()
        );
        $this->assertSame(['Préstamo empresa (cuota 1/6)'], $data['sections']['other']->pluck('label')->all());

        // Cada sección suma exactamente el total que guardó el motor.
        $this->assertSame($data['totals']['taxable'], $data['sections']['taxable']->sum('amount'));
        $this->assertSame($data['totals']['non_taxable'], $data['sections']['non_taxable']->sum('amount'));
        $this->assertSame($data['totals']['other'], $data['sections']['other']->sum('amount'));
        $this->assertSame(2060000, $data['totals']['earnings']);
        $this->assertSame(454504, $data['totals']['deductions']);
        $this->assertSame(1605496, $data['totals']['net']);
        $this->assertSame(
            'un millón seiscientos cinco mil cuatrocientos noventa y seis pesos',
            $data['netInWords']
        );
        $this->assertFalse($data['isIssued']);
    }

    public function test_company_admin_downloads_the_payslip_as_pdf(): void
    {
        $employee = $this->employee(['salary' => 900000, 'contract_type' => 'indefinido', 'rut' => '11111111-1']);
        $line = $this->calculate($employee);

        $response = $this->get(route('companies.employees.payrolls.pdf', [$this->company, $employee, $line]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString(
            'liquidacion-11111111-1-2026-03.pdf',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_payslip_of_another_employee_is_not_served_under_a_different_ficha(): void
    {
        $ana = $this->employee(['salary' => 900000, 'contract_type' => 'indefinido']);
        $luis = $this->employee(['salary' => 800000, 'contract_type' => 'indefinido', 'first_name' => 'Luis']);
        $this->calculate($ana);

        $this->get(route('companies.employees.payrolls.pdf', [$this->company, $luis, $this->line($ana)]))
            ->assertNotFound();
    }

    public function test_employee_sees_and_downloads_payslips_only_after_the_period_is_closed(): void
    {
        $employee = $this->employee(['salary' => 900000, 'contract_type' => 'indefinido']);
        $this->assign($employee, Item::TYPE_HABER_NO_IMPONIBLE, 'Bono de locomoción', 45000);
        $line = $this->calculate($employee);

        // Período sin cerrar: la liquidación todavía no se publica.
        $this->actAsEmployee($employee);
        $this->get(route('employee.profile.show'))
            ->assertOk()
            ->assertSee('Sin liquidaciones publicadas')
            ->assertSee('Bono de locomoción')
            ->assertDontSee('Marzo 2025');
        $this->get(route('employee.payslips.pdf', $line->id))->assertNotFound();

        $this->closePeriod();

        $this->actAsEmployee($employee);
        $this->get(route('employee.profile.show'))
            ->assertOk()
            ->assertSee('Liquidación')
            ->assertSee('Marzo')
            ->assertSee(number_format((int) $line->net_salary, 0, ',', '.'))
            ->assertDontSee('Sin liquidaciones publicadas');

        $response = $this->get(route('employee.payslips.pdf', $line->id));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_employee_cannot_download_the_payslip_of_a_coworker(): void
    {
        $ana = $this->employee(['salary' => 900000, 'contract_type' => 'indefinido']);
        $luis = $this->employee(['salary' => 800000, 'contract_type' => 'indefinido', 'first_name' => 'Luis']);
        $this->calculate($ana);
        $luisLine = $this->line($luis);
        $this->closePeriod();

        $this->actAsEmployee($ana);
        $this->get(route('employee.payslips.pdf', $luisLine->id))->assertNotFound();
    }

    private function actAsAdmin(): void
    {
        app(TenantContext::class)->initializeForUser($this->actor);
        $this->actingAs($this->actor);
    }

    /**
     * Sesión real de un colaborador: rol personal `employee`, sin empresa seleccionada
     * ni contexto de tenant heredado del administrador.
     */
    private function actAsEmployee(Employee $employee): void
    {
        Role::findOrCreate('employee');
        $user = $employee->user()->first();
        $user->assignRole('employee');

        $this->flushSession();
        app(TenantContext::class)->clear();
        $this->actingAs($user);
    }

    private function closePeriod(): void
    {
        app(TenantContext::class)->bypass();
        $this->period->update(['status' => PayrollPeriod::STATUS_CLOSED]);
        app(TenantContext::class)->bypass(false);
    }

    private function employee(array $attributes): Employee
    {
        app(TenantContext::class)->bypass();
        $user = User::factory()->create();
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'status' => 'active',
        ], $attributes));
        app(TenantContext::class)->bypass(false);
        app(TenantContext::class)->initializeForUser($this->actor);

        return $employee;
    }

    private function assign(Employee $employee, string $type, string $name, int $amount, array $extra = []): EmployeeItem
    {
        $item = Item::create([
            'company_id' => $this->company->id,
            'name' => $name,
            'code' => strtoupper(substr(md5($name), 0, 8)),
            'type' => $type,
        ]);

        return EmployeeItem::create(array_merge([
            'employee_id' => $employee->id,
            'item_id' => $item->id,
            'amount' => $amount,
            'unit' => 'CLP',
            'periodicity' => 'fixed',
            'is_active' => true,
        ], $extra));
    }

    private function calculate(Employee $employee): Payroll
    {
        $this->actAsAdmin();
        $this->post(route('companies.payroll-periods.calculate', [$this->company, $this->period]))
            ->assertRedirect()->assertSessionHasNoErrors();
        app(TenantContext::class)->initializeForUser($this->actor);

        return $this->line($employee);
    }

    private function line(Employee $employee): ?Payroll
    {
        return $this->period->payrolls()->where('employee_id', $employee->id)->first();
    }
}
