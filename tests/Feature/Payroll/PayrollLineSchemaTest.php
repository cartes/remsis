<?php

namespace Tests\Feature\Payroll;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollLine;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Users\Models\User;
use Tests\TestCase;

class PayrollLineSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_payrolls_table_contains_line_fields_used_by_payrollline(): void
    {
        $this->assertTrue(
            Schema::hasColumns('payrolls', ['overtime_amount', 'gratification_amount'])
        );
    }

    public function test_payroll_line_can_persist_calculated_amount_fields(): void
    {
        $company = Company::create([
            'name' => 'Empresa Payroll',
            'razon_social' => 'Empresa Payroll SpA',
            'rut' => '12345678-9',
        ]);

        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $period = PayrollPeriod::create([
            'company_id' => $company->id,
            'year' => 2026,
            'month' => 3,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);

        $line = PayrollLine::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'payroll_period_id' => $period->id,
            'period_year' => 2026,
            'period_month' => 3,
            'worked_days' => 30,
            'overtime_hours' => 5,
            'overtime_amount' => 25000,
            'base_salary' => 800000,
            'gratification_amount' => 50000,
            'gross_salary' => 875000,
            'total_deductions' => 120000,
            'net_salary' => 755000,
            'status' => 'pending',
        ]);

        $this->assertSame(25000.0, (float) $line->fresh()->overtime_amount);
        $this->assertSame(50000.0, (float) $line->fresh()->gratification_amount);
    }

    public function test_payroll_line_remains_a_legacy_view_of_the_same_payroll_row(): void
    {
        $company = Company::create([
            'name' => 'Empresa Legacy',
            'razon_social' => 'Empresa Legacy SpA',
            'rut' => '22345678-9',
        ]);

        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $period = PayrollPeriod::create([
            'company_id' => $company->id,
            'year' => 2026,
            'month' => 4,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);

        $payroll = Payroll::create([
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'payroll_period_id' => $period->id,
            'period_year' => 2026,
            'period_month' => 4,
            'worked_days' => 30,
            'base_salary' => 900000,
            'gross_salary' => 900000,
            'total_deductions' => 100000,
            'net_salary' => 800000,
            'status' => 'pending',
        ]);

        $legacyLine = PayrollLine::findOrFail($payroll->id);

        $this->assertSame($payroll->id, $legacyLine->id);
        $this->assertSame($payroll->getTable(), $legacyLine->getTable());
        $this->assertSame(800000.0, (float) $legacyLine->net_salary);
    }
}
