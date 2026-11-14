<?php

namespace Tests\Feature\Payroll;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Services\ProcessEmployeePayroll;
use Modules\Users\Models\User;
use Tests\TestCase;

class MonthlyPayrollEarningsTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private Company $company;

    private PayrollPeriod $period;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Company::create([
            'name' => 'Empresa Flow',
            'razon_social' => 'Empresa Flow SpA',
            'rut' => '33333333-3',
            'gratification_system' => Company::GRATIFICATION_SYSTEM_ART_50,
            'weekly_hours' => 45,
            'allows_overtime' => true,
        ]);

        app(TenantContext::class)->bypass();
        $actor = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $employeeUser = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'company_id' => $company->id,
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'rut' => '11.111.111-1',
            'salary' => 500000,
            'status' => 'active',
        ]);

        $period = PayrollPeriod::create([
            'company_id' => $company->id,
            'year' => 2026,
            'month' => 2,
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);
        app(TenantContext::class)->bypass(false);

        app(TenantContext::class)->initializeForUser($actor);

        $this->actor = $actor;
        $this->company = $company;
        $this->period = $period;
        $this->employee = $employee;
        $this->actingAs($actor);
    }

    public function test_commission_enters_both_gratification_calculations(): void
    {
        $line = $this->calculate();
        $this->assertEquals(125000, $line->gratification_amount);
        $this->putJson(route('companies.payroll-periods.update-line', [$this->company, $this->period, $line]), [
            'details' => [['type' => 'CO01', 'description' => 'Comisión', 'amount' => 100000]],
        ])->assertOk();
        app(TenantContext::class)->initializeForUser($this->actor);
        $this->assertEquals(150000, $line->fresh()->gratification_amount);
        $result = (new ProcessEmployeePayroll)->execute($this->employee->id, $this->period->id);
        $this->assertEquals(150000, $result->gratification);
        $this->assertEquals(750000, $result->taxable_earnings);
        $this->assertEquals(100000, $line->details()->where('concept', 'CO01')->sum('amount'));
    }

    public function test_editing_absences_and_full_february_leave(): void
    {
        $this->employee->update(['salary' => 900000]);
        $this->company->update(['gratification_system' => Company::GRATIFICATION_SYSTEM_NONE]);
        $this->mark(2, 'ausente', false, 'inasistencia_injustificada');
        $this->mark(3, 'ausente', true, 'permiso_con_goce');
        $this->assertEquals(870000, $this->calculate()->gross_salary);

        for ($day = 1; $day <= 28; $day++) {
            $this->mark($day, 'licencia', true, 'licencia_medica');
        }

        $line = $this->calculate();
        $this->assertDatabaseCount('attendance_records', 28);
        $this->assertEquals(0, $line->worked_days);
        $this->assertEquals(0, $line->gross_salary);
        $individual = (new ProcessEmployeePayroll)->execute($this->employee->id, $this->period->id);
        $this->assertEquals(0, $individual->worked_days);
        $this->assertEquals(0, $individual->taxable_earnings);
    }

    private function calculate(): Payroll
    {
        $this->post(route('companies.payroll-periods.calculate', [$this->company, $this->period]))
            ->assertRedirect()->assertSessionHasNoErrors();
        app(TenantContext::class)->initializeForUser($this->actor);

        return $this->period->payrolls()->where('employee_id', $this->employee->id)->firstOrFail();
    }

    private function mark(int $day, string $status, bool $justified, string $reason): void
    {
        $this->post(route('companies.employees.attendance.store', [$this->company, $this->employee]), [
            'date' => sprintf('2026-02-%02d', $day),
            'status' => $status,
            'is_justified' => $justified,
            'justification_type' => $reason,
        ])->assertRedirect()->assertSessionHasNoErrors();
    }
}
