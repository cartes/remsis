<?php

namespace Tests\Feature\Payroll;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Users\Models\User;
use Tests\TestCase;

class PayrollConsolidationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_and_update_line_flow_uses_canonical_payroll_model(): void
    {
        $company = Company::create([
            'name' => 'Empresa Flow',
            'razon_social' => 'Empresa Flow SpA',
            'rut' => '33333333-3',
            'gratification_system' => Company::GRATIFICATION_SYSTEM_CONVENTIONAL,
            'weekly_hours' => 45,
            'allows_overtime' => true,
        ]);

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
            'month' => 3,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);

        $calculateResponse = $this->actingAs($actor)->post(
            route('companies.payroll-periods.calculate', ['company' => $company, 'period' => $period])
        );

        $calculateResponse->assertRedirect(
            route('companies.payroll-periods.wizard', ['company' => $company, 'period' => $period->id])
        );

        $payroll = Payroll::where('employee_id', $employee->id)
            ->where('payroll_period_id', $period->id)
            ->firstOrFail();

        $this->assertSame(500000.0, (float) $payroll->base_salary);
        $this->assertSame('calculated', $period->fresh()->status);

        $updateResponse = $this->actingAs($actor)->putJson(
            route('companies.payroll-periods.update-line', ['company' => $company, 'period' => $period, 'line' => $payroll->id]),
            [
                'overtime_hours' => 2,
                'gratification_amount' => 25000,
                'otros_descuentos' => 3000,
            ]
        );

        $updateResponse->assertOk()
            ->assertJson(['success' => true]);

        $updatedPayroll = $payroll->fresh();

        $this->assertSame($payroll->id, $updatedPayroll->id);
        $this->assertSame(2.0, (float) $updatedPayroll->overtime_hours);
        $this->assertSame(25000.0, (float) $updatedPayroll->gratification_amount);
        $this->assertSame(3000.0, (float) $updatedPayroll->otros_descuentos);
        $this->assertGreaterThan(0, (float) $updatedPayroll->overtime_amount);
        $this->assertSame(
            round(
                $updatedPayroll->afp_amount
                + $updatedPayroll->isapre_amount
                + $updatedPayroll->cesantia_amount
                + $updatedPayroll->anticipos_amount
                + $updatedPayroll->otros_descuentos
            ),
            round((float) $updatedPayroll->total_deductions)
        );
    }
}
