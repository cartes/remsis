<?php

namespace Tests\Feature\Employees;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\MedicalLeave;
use Modules\Employees\Models\VacationRequest;
use Modules\Employees\Services\VacationService;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Users\Models\User;
use Tests\Support\CreatesMonthlyIndicator;
use Tests\TestCase;

/**
 * Licencias médicas: asistencia, carencia, continuidad, resolución y efecto en la nómina.
 *
 * Sueldo de referencia $900.000 sin AFP informada (10%), Fonasa e indefinido:
 * neto = 900.000 − 90.000 (AFP) − 63.000 (salud) − 5.400 (cesantía) = 741.600; subsidio diario 24.720.
 */
class MedicalLeaveTest extends TestCase
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
            'name' => 'Empresa Licencias',
            'razon_social' => 'Empresa Licencias SpA',
            'rut' => '66666666-6',
            'gratification_system' => Company::GRATIFICATION_SYSTEM_NONE,
            'weekly_hours' => 42,
        ]);

        app(TenantContext::class)->bypass();
        $this->actor = User::factory()->create(['company_id' => $this->company->id]);
        app(TenantContext::class)->bypass(false);

        $this->actAsAdmin();
        $this->createMonthlyIndicator(2026, 11);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_short_common_leave_marks_attendance_and_applies_the_waiting_period(): void
    {
        $employee = $this->employee();

        $this->register($employee, ['folio' => 'A-100', 'start_date' => '2026-12-01', 'days' => 7])
            ->assertSessionHasNoErrors();

        $leave = $this->leave('A-100');
        $this->assertSame('in_process', $leave->status);
        $this->assertSame('2026-12-07', $leave->end_date->toDateString());
        $this->assertSame('compin', $leave->payer_type);

        // Sin liquidaciones previas, la base es la remuneración neta del contrato.
        $this->assertEquals(741600, $leave->subsidy_base_amount);
        $this->assertEquals(24720, $leave->subsidy_daily_amount);
        $this->assertSame(['contrato', 'contrato', 'contrato'], array_column($leave->subsidy_detail['months'], 'source'));

        // Hasta 10 días: tres de carencia.
        $this->assertSame(4, $leave->subsidy_days);
        $this->assertSame(3, $leave->waiting_days);
        $this->assertEquals(98880, $leave->subsidy_amount);

        // Días corridos, incluidos sábado 5 y domingo 6.
        $this->assertSame(7, AttendanceRecord::where('employee_id', $employee->id)
            ->where('status', 'licencia')->where('justification_type', 'licencia_medica')->count());

        // La empresa no paga los días de licencia, pero entera el SIS y su cesantía por ellos.
        $line = $this->calculate($employee, 2026, 12);
        $this->assertEquals(23, $line->worked_days);
        $this->assertEquals(7, $line->medical_leave_days);
        $this->assertEquals(690000, $line->gross_salary);
        $this->assertEquals(10350 + 3150, $line->employer_sis_amount);   // 1,5% de 690.000 + 1,5% de 210.000
        $this->assertEquals(16560 + 5040, $line->employer_afc_amount);   // 2,4% de 690.000 + 2,4% de 210.000
    }

    public function test_continuous_leaves_are_added_up_to_decide_the_waiting_period(): void
    {
        $employee = $this->employee();

        $this->register($employee, ['folio' => 'B-1', 'start_date' => '2026-12-01', 'days' => 7])->assertSessionHasNoErrors();
        $this->register($employee, ['folio' => 'B-2', 'start_date' => '2026-12-08', 'days' => 5, 'is_continuation' => 1])
            ->assertSessionHasNoErrors();

        // 12 días continuos: se paga desde el primero, también en la licencia ya registrada.
        $this->assertSame(7, $this->leave('B-1')->subsidy_days);
        $this->assertSame(5, $this->leave('B-2')->subsidy_days);
        $this->assertSame($this->leave('B-1')->id, $this->leave('B-2')->continues_leave_id);

        // Al anular la continuación, la primera vuelve a tener carencia y se libera la asistencia.
        $this->post(route('companies.employees.medical-leaves.cancel', [$this->company, $employee, $this->leave('B-2')]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->actAsAdmin();

        $this->assertSame('cancelled', $this->leave('B-2')->status);
        $this->assertSame(4, $this->leave('B-1')->subsidy_days);
        $this->assertSame(7, AttendanceRecord::where('employee_id', $employee->id)->where('status', 'licencia')->count());
    }

    public function test_continuation_requires_a_leave_ending_the_day_before(): void
    {
        $employee = $this->employee();
        $this->register($employee, ['folio' => 'C-1', 'start_date' => '2026-12-01', 'days' => 3])->assertSessionHasNoErrors();

        $this->register($employee, ['folio' => 'C-2', 'start_date' => '2026-12-10', 'days' => 3, 'is_continuation' => 1])
            ->assertSessionHasErrorsIn('leave', ['is_continuation']);

        $this->assertSame(1, MedicalLeave::where('employee_id', $employee->id)->count());
    }

    public function test_resolution_changes_the_days_with_subsidy(): void
    {
        $employee = $this->employee();
        $this->register($employee, ['folio' => 'D-1', 'start_date' => '2026-12-01', 'days' => 15])->assertSessionHasNoErrors();
        $leave = $this->leave('D-1');
        $this->assertSame(15, $leave->subsidy_days);

        // Reducida a 8 días: queda bajo el límite y aparece la carencia.
        $this->resolve($employee, $leave, ['status' => 'reduced', 'authorized_days' => 8])->assertSessionHasNoErrors();
        $leave->refresh();
        $this->assertSame('reduced', $leave->status);
        $this->assertSame(8, $leave->effective_days);
        $this->assertSame(5, $leave->subsidy_days);
        $this->assertEquals(5 * 24720, $leave->subsidy_amount);

        // La nómina sigue sin pagar los 15 días, pero sólo 8 son de licencia reconocida.
        $line = $this->calculate($employee, 2026, 12);
        $this->assertEquals(15, $line->worked_days);
        $this->assertEquals(8, $line->medical_leave_days);

        // Rechazada (por ejemplo, tras revisar la apelación): sin subsidio.
        $this->resolve($employee, $leave, ['status' => 'rejected', 'resolution_notes' => 'Reposo no justificado'])
            ->assertSessionHasNoErrors();
        $leave->refresh();
        $this->assertSame(0, $leave->subsidy_days);
        $this->assertEquals(0, $leave->subsidy_amount);

        // Una reducción debe autorizar menos días que los prescritos.
        $this->resolve($employee, $leave, ['status' => 'reduced', 'authorized_days' => 15])
            ->assertSessionHasErrorsIn('leave', ['authorized_days']);
    }

    public function test_subsidy_base_uses_the_previous_three_payslips(): void
    {
        $employee = $this->employee();
        foreach ([8, 9, 10] as $month) {
            $this->calculate($employee, 2026, $month);
        }
        // Octubre con un bono: neto = 1.200.000 − 120.000 − 84.000 − 7.200 − impuesto.
        $october = Payroll::where('employee_id', $employee->id)->where('period_month', 10)->firstOrFail();
        $october->update(['gross_salary' => 1200000, 'afp_amount' => 120000, 'isapre_amount' => 84000, 'cesantia_amount' => 7200, 'impuesto_unico_amount' => 1752]);

        $this->register($employee, ['folio' => 'E-1', 'start_date' => '2026-11-10', 'days' => 11])->assertSessionHasNoErrors();
        $leave = $this->leave('E-1');

        $this->assertSame(['liquidacion', 'liquidacion', 'liquidacion'], array_column($leave->subsidy_detail['months'], 'source'));
        $this->assertSame(['2026-10', '2026-09', '2026-08'], array_column($leave->subsidy_detail['months'], 'period'));
        // (987.048 + 741.600 + 741.600) / 3
        $this->assertEquals(823416, $leave->subsidy_base_amount);
        $this->assertEquals(27447.2, $leave->subsidy_daily_amount);
        $this->assertSame(11, $leave->subsidy_days);
    }

    public function test_work_accident_is_paid_by_the_work_insurance_without_waiting_period(): void
    {
        $employee = $this->employee();

        $this->register($employee, ['folio' => 'F-1', 'type' => 'accidente_trabajo', 'start_date' => '2026-12-01', 'days' => 5])
            ->assertSessionHasNoErrors();

        $leave = $this->leave('F-1');
        $this->assertSame('isl', $leave->payer_type);
        $this->assertSame(5, $leave->subsidy_days);
    }

    public function test_full_month_leave_pays_no_salary_and_counts_thirty_days(): void
    {
        $employee = $this->employee();
        $this->createMonthlyIndicator(2027, 2);

        $this->register($employee, ['folio' => 'G-1', 'start_date' => '2027-02-01', 'days' => 28])->assertSessionHasNoErrors();

        $line = $this->calculate($employee, 2027, 2);
        $this->assertEquals(0, $line->worked_days);
        $this->assertEquals(0, $line->gross_salary);
        $this->assertEquals(30, $line->medical_leave_days);
        $this->assertEquals(13500, $line->employer_sis_amount); // 1,5% de 900.000
    }

    public function test_overlaps_duplicates_vacations_and_closed_periods_are_rejected(): void
    {
        $employee = $this->employee();
        $this->register($employee, ['folio' => 'H-1', 'start_date' => '2026-12-01', 'days' => 5])->assertSessionHasNoErrors();

        $this->register($employee, ['folio' => 'H-2', 'start_date' => '2026-12-05', 'days' => 3])
            ->assertSessionHasErrorsIn('leave', ['start_date']);
        $this->register($employee, ['folio' => 'H-1', 'start_date' => '2026-12-20', 'days' => 2])
            ->assertSessionHasErrorsIn('leave', ['folio']);

        $vacations = app(VacationService::class);
        $vacations->approve(
            $vacations->request($employee, '2026-12-14', '2026-12-18', null, VacationRequest::ORIGIN_COMPANY),
            $this->actor
        );
        $this->register($employee, ['folio' => 'H-3', 'start_date' => '2026-12-16', 'days' => 3])
            ->assertSessionHasErrorsIn('leave', ['start_date']);

        $this->period(2026, 10, PayrollPeriod::STATUS_CLOSED);
        $this->register($employee, ['folio' => 'H-4', 'start_date' => '2026-10-05', 'days' => 3])
            ->assertSessionHasErrorsIn('leave', ['start_date']);

        $this->assertSame(1, MedicalLeave::where('employee_id', $employee->id)->count());
        $this->assertSame(5, AttendanceRecord::where('employee_id', $employee->id)->where('status', 'licencia')->count());
    }

    public function test_leave_of_another_company_employee_is_not_reachable(): void
    {
        $ana = $this->employee();
        $luis = $this->employee(['first_name' => 'Luis']);
        $this->register($luis, ['folio' => 'I-1', 'start_date' => '2026-12-01', 'days' => 5])->assertSessionHasNoErrors();

        // La licencia de Luis no se resuelve desde la ficha de Ana.
        $this->post(route('companies.employees.medical-leaves.cancel', [$this->company, $ana, $this->leave('I-1')]))
            ->assertNotFound();
        $this->actAsAdmin();

        $this->assertSame('in_process', $this->leave('I-1')->status);
    }

    public function test_ficha_renders_the_medical_leaves_tab(): void
    {
        $employee = $this->employee();
        $this->register($employee, ['folio' => 'J-777', 'start_date' => '2026-12-01', 'days' => 7])->assertSessionHasNoErrors();

        $this->get(route('companies.employees.edit', [$this->company, $employee]))
            ->assertOk()
            ->assertSee('Registrar licencia')
            ->assertSee('Folio J-777')
            ->assertSee('3 días de carencia')
            ->assertSee('$98.880');
    }

    // ── Apoyo ────────────────────────────────────────────────────────────────

    private function register(Employee $employee, array $data)
    {
        $response = $this->post(
            route('companies.employees.medical-leaves.store', [$this->company, $employee]),
            $data + ['type' => 'enfermedad_comun']
        );
        $this->actAsAdmin();

        return $response;
    }

    private function resolve(Employee $employee, MedicalLeave $leave, array $data)
    {
        $response = $this->post(route('companies.employees.medical-leaves.resolve', [$this->company, $employee, $leave]), $data);
        $this->actAsAdmin();

        return $response;
    }

    private function leave(string $folio): MedicalLeave
    {
        return MedicalLeave::where('folio', $folio)->sole();
    }

    private function actAsAdmin(): void
    {
        app(TenantContext::class)->initializeForUser($this->actor);
        $this->actingAs($this->actor);
    }

    private function employee(array $attributes = []): Employee
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
            'salary' => 900000,
            'hire_date' => '2024-01-01',
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
