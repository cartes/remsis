<?php

namespace Tests\Feature\Employees;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CompanyAdministrativeDayPolicy;
use Modules\Employees\Models\AdministrativeDayRequest;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmploymentContract;
use Modules\Employees\Models\VacationRequest;
use Modules\Employees\Services\EmploymentContractService;
use Modules\Employees\Services\VacationService;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\Support\CreatesMonthlyIndicator;
use Tests\TestCase;

/**
 * Recursos Humanos: historial de contratos con vigencia y feriado anual.
 */
class HumanResourcesTest extends TestCase
{
    use CreatesMonthlyIndicator;
    use RefreshDatabase;

    private User $actor;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-16 10:00:00');

        $this->company = Company::create([
            'name' => 'Empresa RRHH',
            'razon_social' => 'Empresa RRHH SpA',
            'rut' => '66666666-6',
            'gratification_system' => Company::GRATIFICATION_SYSTEM_NONE,
            'weekly_hours' => 42,
        ]);

        app(TenantContext::class)->bypass();
        $this->actor = User::factory()->create(['company_id' => $this->company->id]);
        app(TenantContext::class)->bypass(false);

        $this->actAsAdmin();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── Contratos ────────────────────────────────────────────────────────────

    public function test_amendment_keeps_the_previous_salary_for_earlier_months(): void
    {
        $employee = $this->employee(['salary' => 1000000, 'hire_date' => '2025-01-01']);

        $this->post(route('companies.employees.contracts.amend', [$this->company, $employee]), [
            'effective_date' => '2026-04-01',
            'base_salary' => 1200000,
            'contract_type' => 'indefinido',
            'work_schedule_type' => 'full_time',
            'weekly_hours' => 42,
            'change_reason' => 'Reajuste anual',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actAsAdmin();

        $versions = EmploymentContract::where('employee_id', $employee->id)->orderBy('valid_from')->get();
        $this->assertCount(2, $versions);
        $this->assertSame('2026-03-31', $versions[0]->valid_until->toDateString());
        $this->assertEquals(1000000, $versions[0]->base_salary);
        $this->assertSame('anexo', $versions[1]->version_type);
        $this->assertSame('2026-04-01', $versions[1]->valid_from->toDateString());
        $this->assertNull($versions[1]->valid_until);

        // El anexo ya rige: la ficha muestra el sueldo nuevo.
        $this->assertEquals(1200000, $employee->fresh()->salary);

        // Marzo se liquida con el sueldo anterior y abril con el nuevo.
        $this->assertEquals(1000000, $this->calculate($employee, 2026, 3)->base_salary);
        $this->assertEquals(1200000, $this->calculate($employee, 2026, 4)->base_salary);
    }

    public function test_future_amendment_reaches_the_ficha_only_when_it_comes_into_force(): void
    {
        $employee = $this->employee(['salary' => 1000000, 'hire_date' => '2025-01-01', 'position' => 'Analista']);

        app(EmploymentContractService::class)->registerAmendment($employee, [
            'effective_date' => '2027-01-01',
            'base_salary' => 1500000,
            'position' => 'Jefa de área',
            'change_reason' => 'Ascenso',
        ], $this->actor);

        $this->assertEquals(1000000, $employee->fresh()->salary);
        $this->assertSame('Analista', $employee->fresh()->position);

        Carbon::setTestNow('2027-01-02 08:00:00');
        $this->artisan('remsis:apply-contract-versions')->assertSuccessful();
        $this->actAsAdmin();

        $this->assertEquals(1500000, $employee->fresh()->salary);
        $this->assertSame('Jefa de área', $employee->fresh()->position);
        // La versión anterior conserva sus condiciones.
        $this->assertEquals(1000000, EmploymentContract::inForceFor($employee->id, '2026-12-31')->base_salary);
    }

    public function test_amendment_cannot_start_before_the_version_it_modifies(): void
    {
        $employee = $this->employee(['salary' => 1000000, 'hire_date' => '2025-01-01']);

        $this->post(route('companies.employees.contracts.amend', [$this->company, $employee]), [
            'effective_date' => '2024-12-01',
            'base_salary' => 900000,
            'contract_type' => 'indefinido',
            'work_schedule_type' => 'full_time',
            'weekly_hours' => 42,
            'change_reason' => 'Fecha inválida',
        ])->assertSessionHasErrorsIn('amendment', ['effective_date']);

        $this->assertSame(1, EmploymentContract::withoutGlobalScopes()->where('employee_id', $employee->id)->count());
    }

    // ── Vacaciones ───────────────────────────────────────────────────────────

    public function test_balance_accrues_fifteen_working_days_per_year_of_service(): void
    {
        $employee = $this->employee(['hire_date' => '2024-11-16']);

        $balance = app(VacationService::class)->balance($employee);

        $this->assertSame(30.0, $balance['accrued']);
        $this->assertSame(30.0, $balance['available']);
        $this->assertSame(2, $balance['years_of_service']);
        $this->assertTrue($balance['entitled']);
        $this->assertFalse($balance['exceeds_accumulation']);
    }

    public function test_progressive_vacation_adds_a_day_every_three_years_after_ten(): void
    {
        // 10 años acreditados con otros empleadores + ingreso en 2020: aniversarios 2021 a 2026.
        // Años totales 11, 12, 13, 14, 15, 16 → días progresivos 0, 0, 1, 1, 1, 2.
        $employee = $this->employee(['hire_date' => '2020-01-01', 'previous_employment_months' => 200]);

        $balance = app(VacationService::class)->balance($employee);

        $this->assertSame(5.0, $balance['progressive']);
        $this->assertSame(2, $balance['progressive_per_year']);
    }

    public function test_opening_balance_replaces_the_accrual_before_its_date(): void
    {
        $employee = $this->employee([
            'hire_date' => '2020-01-01',
            'vacation_initial_balance' => 8,
            'vacation_initial_balance_date' => '2026-05-16',
        ]);

        $balance = app(VacationService::class)->balance($employee);

        // 8 de apertura + 6 meses a 1,25 días.
        $this->assertSame(7.5, $balance['accrued']);
        $this->assertSame(15.5, $balance['available']);
    }

    public function test_weekends_and_holidays_are_not_counted(): void
    {
        // Lunes 14 a domingo 20 de septiembre de 2026: el viernes 18 es festivo.
        $days = app(VacationService::class)->workingDays(Carbon::parse('2026-09-14'), Carbon::parse('2026-09-20'));

        $this->assertSame(4, $days);
    }

    public function test_employee_request_is_approved_and_paid_as_worked_days(): void
    {
        $employee = $this->employee(['salary' => 900000, 'hire_date' => '2024-01-01']);

        $this->actAsEmployee($employee);
        $this->post(route('employee.vacations.store'), ['start_date' => '2026-12-07', 'end_date' => '2026-12-11'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $request = VacationRequest::withoutGlobalScopes()->where('employee_id', $employee->id)->sole();
        $this->assertSame('pending', $request->status);
        // El martes 8 de diciembre es festivo.
        $this->assertSame(4.0, $request->working_days);

        $this->actAsAdmin();
        $this->post(route('companies.employees.vacations.approve', [$this->company, $employee, $request]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->actAsAdmin();

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame(
            ['2026-12-07', '2026-12-09', '2026-12-10', '2026-12-11'],
            AttendanceRecord::where('employee_id', $employee->id)->where('status', 'vacaciones')
                ->orderBy('date')->get()->map(fn ($r) => $r->date->toDateString())->all()
        );
        $this->assertSame(4.0, app(VacationService::class)->balance($employee)['used']);

        // Las vacaciones se pagan: el mes se liquida con 30 días.
        $line = $this->calculate($employee, 2026, 12);
        $this->assertEquals(30, $line->worked_days);
        $this->assertEquals(900000, $line->gross_salary);

        // Al anular se liberan la asistencia y el saldo.
        $this->post(route('companies.employees.vacations.cancel', [$this->company, $employee, $request]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->actAsAdmin();

        $this->assertSame('cancelled', $request->fresh()->status);
        $this->assertSame(0, AttendanceRecord::where('employee_id', $employee->id)->count());
        $this->assertSame(0.0, app(VacationService::class)->balance($employee)['used']);
    }

    public function test_employee_cannot_request_more_days_than_the_available_balance(): void
    {
        $employee = $this->employee(['hire_date' => '2026-09-16']); // 2 meses: 2,5 días

        $this->actAsEmployee($employee);
        $this->post(route('employee.vacations.store'), ['start_date' => '2026-11-23', 'end_date' => '2026-11-27'])
            ->assertSessionHasErrorsIn('vacation', ['start_date']);

        $this->assertSame(0, VacationRequest::withoutGlobalScopes()->count());
    }

    public function test_overlapping_requests_and_closed_periods_are_rejected(): void
    {
        $employee = $this->employee(['hire_date' => '2020-01-01']);
        $service = app(VacationService::class);
        $service->request($employee, '2026-12-14', '2026-12-18', null, VacationRequest::ORIGIN_COMPANY);

        try {
            $service->request($employee, '2026-12-18', '2026-12-22', null, VacationRequest::ORIGIN_COMPANY);
            $this->fail('Se aceptó una solicitud que se cruza con otra.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('start_date', $e->errors());
        }

        $this->period(2026, 10, PayrollPeriod::STATUS_CLOSED);
        $this->post(route('companies.employees.vacations.store', [$this->company, $employee]), [
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-09',
        ])->assertSessionHasErrorsIn('vacation', ['start_date']);
    }

    public function test_employee_cannot_cancel_the_request_of_a_coworker(): void
    {
        $ana = $this->employee(['hire_date' => '2020-01-01']);
        $luis = $this->employee(['hire_date' => '2020-01-01', 'first_name' => 'Luis']);
        $request = app(VacationService::class)->request($luis, '2026-12-14', '2026-12-18', null, VacationRequest::ORIGIN_EMPLOYEE);

        $this->actAsEmployee($ana);
        $this->post(route('employee.vacations.cancel', $request->id))->assertNotFound();

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_employee_without_admin_role_can_request_and_cancel_an_administrative_day(): void
    {
        $employee = $this->employee(['hire_date' => '2020-01-01']);
        CompanyAdministrativeDayPolicy::create([
            'company_id' => $this->company->id,
            'is_enabled' => true,
            'days_per_cycle' => 3,
            'unit' => 'full_day',
            'cycle_type' => 'annual',
        ]);

        // El aviso a los encargados busca usuarios por rol; en producción los crea el seeder de roles.
        foreach (['admin', 'contador', 'recursos-humanos', 'multi-company', 'super-admin'] as $role) {
            Role::findOrCreate($role);
        }

        // El colaborador no administra ninguna empresa: el portal debe operar con la de su propio vínculo.
        $this->actAsEmployee($employee);
        $this->post(route('employee.requests.store'), ['requested_date' => '2026-12-10'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $request = AdministrativeDayRequest::withoutGlobalScopes()
            ->where('employee_id', $employee->id)->sole();
        $this->assertSame('pending', $request->status);

        $this->get(route('employee.profile.show'))->assertOk()->assertSee('10/12/2026');

        $this->post(route('employee.requests.cancel', $request->id))->assertRedirect();
        $this->assertSame('cancelled', $request->fresh()->status);
    }

    public function test_ficha_and_portal_render_contracts_and_vacations(): void
    {
        $employee = $this->employee(['salary' => 1000000, 'hire_date' => '2024-11-16']);

        $this->get(route('companies.employees.edit', [$this->company, $employee]))
            ->assertOk()
            ->assertSee('Historial contractual')
            ->assertSee('Registrar vacaciones');

        $this->actAsEmployee($employee);
        $this->get(route('employee.profile.show'))
            ->assertOk()
            ->assertSee('Solicitar vacaciones');
    }

    // ── Apoyo ────────────────────────────────────────────────────────────────

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
            'contract_type' => 'indefinido',
        ], $attributes));
        app(TenantContext::class)->bypass(false);
        app(TenantContext::class)->initializeForUser($this->actor);

        return $employee;
    }

    private function period(int $year, int $month, string $status = PayrollPeriod::STATUS_DRAFT): PayrollPeriod
    {
        app(TenantContext::class)->bypass();
        $start = Carbon::create($year, $month, 1);
        $period = PayrollPeriod::create([
            'company_id' => $this->company->id,
            'year' => $year,
            'month' => $month,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->endOfMonth()->toDateString(),
            'status' => $status,
        ]);
        app(TenantContext::class)->bypass(false);
        app(TenantContext::class)->initializeForUser($this->actor);

        return $period;
    }

    private function calculate(Employee $employee, int $year, int $month): Payroll
    {
        $this->createMonthlyIndicator($year, $month);
        $period = $this->period($year, $month);

        $this->actAsAdmin();
        $this->post(route('companies.payroll-periods.calculate', [$this->company, $period]))
            ->assertRedirect()->assertSessionHasNoErrors();
        app(TenantContext::class)->initializeForUser($this->actor);

        return $period->payrolls()->where('employee_id', $employee->id)->firstOrFail();
    }
}
