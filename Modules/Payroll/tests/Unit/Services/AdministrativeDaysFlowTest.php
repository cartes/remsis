<?php

declare(strict_types=1);

namespace Modules\Payroll\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\LegalParameter;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CompanyAdministrativeDayPolicy;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Models\EmployeeAdministrativeDayBalance;
use Modules\Employees\Models\AdministrativeDayUsage;
use Modules\Employees\Services\AdministrativeDayProvisioner;
use Modules\Employees\Services\AdministrativeDayAttendanceService;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Services\ProcessEmployeePayroll;
use Modules\Users\Models\User;
use Tests\TestCase;

class AdministrativeDaysFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        app(\App\Support\Tenancy\TenantContext::class)->bypass();

        // Legal parameters setup
        LegalParameter::updateOrCreate(['key' => 'monthly_minimum_wage'], ['value' => 500000]);
        LegalParameter::updateOrCreate(['key' => 'uf_value'], ['value' => 37000]);
        LegalParameter::updateOrCreate(['key' => 'utm_value'], ['value' => 65000]);

        \Spatie\Permission\Models\Role::findOrCreate('employee', 'web');
        \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        \Spatie\Permission\Models\Role::findOrCreate('recursos-humanos', 'web');
        \Spatie\Permission\Models\Role::findOrCreate('contador', 'web');
        \Spatie\Permission\Models\Role::findOrCreate('super-admin', 'web');

        $this->company = Company::create([
            'name' => 'Test Company',
            'rut' => '12.345.678-9',
            'email' => 'test@company.com',
            'gratification_system' => 'sin_gratificacion',
            'gratification_months' => 12,
            'payment_frequency' => 'mensual',
        ]);

        $this->user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'status' => true,
        ]);
        $this->user->company_id = $this->company->id;
        $this->user->save();

        $afp = Afp::create(['nombre' => 'MODELO', 'rate' => 10.0, 'commission' => 0.58]);

        $this->employee = Employee::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'salary' => 1000000,
            'afp_id' => $afp->id,
            'contract_type' => 'indefinido',
            'status' => 'active',
            'is_in_payroll' => true,
        ]);
    }

    /** @test */
    public function it_provisions_balances_for_active_employees_when_policy_is_enabled()
    {
        // 1. Arrange
        $policy = CompanyAdministrativeDayPolicy::create([
            'company_id' => $this->company->id,
            'is_enabled' => true,
            'days_per_cycle' => 3.0,
            'unit' => 'full_day',
            'cycle_type' => 'annual',
        ]);

        $provisioner = app(AdministrativeDayProvisioner::class);

        // 2. Act
        $count = $provisioner->provisionCompany($this->company);

        // 3. Assert
        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('employee_administrative_day_balances', [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'cycle_key' => now()->year,
            'assigned_units' => 3.0,
            'used_units' => 0.0,
            'available_units' => 3.0,
        ]);
    }

    /** @test */
    public function it_consumes_balance_correctly_on_attendance_registration()
    {
        // 1. Arrange
        CompanyAdministrativeDayPolicy::create([
            'company_id' => $this->company->id,
            'is_enabled' => true,
            'days_per_cycle' => 3.0,
            'unit' => 'full_day',
            'cycle_type' => 'annual',
        ]);

        app(AdministrativeDayProvisioner::class)->provisionCompany($this->company);

        $attendanceService = app(AdministrativeDayAttendanceService::class);

        // 2. Act
        $record = $attendanceService->storeOrUpdateAttendance($this->employee, [
            'date' => '2026-05-15',
            'status' => 'ausente',
            'is_justified' => true,
            'justification_type' => 'administrative_day',
            'justification_reason' => 'Trámite personal',
        ]);

        // 3. Assert
        $this->assertInstanceOf(AttendanceRecord::class, $record);
        $this->assertEquals('ausente', $record->status);
        $this->assertTrue($record->is_justified);
        $this->assertEquals('administrative_day', $record->justification_type);

        $this->assertDatabaseHas('employee_administrative_day_balances', [
            'employee_id' => $this->employee->id,
            'used_units' => 1.0,
            'available_units' => 2.0,
        ]);

        $this->assertDatabaseHas('administrative_day_usages', [
            'employee_id' => $this->employee->id,
            'attendance_record_id' => $record->id,
            'used_units' => 1.0,
            'used_on' => '2026-05-15',
        ]);
    }

    /** @test */
    public function it_blocks_registration_if_insufficient_balance()
    {
        // 1. Arrange
        CompanyAdministrativeDayPolicy::create([
            'company_id' => $this->company->id,
            'is_enabled' => true,
            'days_per_cycle' => 1.0,
            'unit' => 'full_day',
            'cycle_type' => 'annual',
        ]);

        app(AdministrativeDayProvisioner::class)->provisionCompany($this->company);

        $attendanceService = app(AdministrativeDayAttendanceService::class);

        // Consume the only available day
        $attendanceService->storeOrUpdateAttendance($this->employee, [
            'date' => '2026-05-15',
            'status' => 'ausente',
            'is_justified' => true,
            'justification_type' => 'administrative_day',
        ]);

        // 2. Act & Assert
        $this->expectException(ValidationException::class);
        $attendanceService->storeOrUpdateAttendance($this->employee, [
            'date' => '2026-05-16',
            'status' => 'ausente',
            'is_justified' => true,
            'justification_type' => 'administrative_day',
        ]);
    }

    /** @test */
    public function it_refunds_balance_when_attendance_record_is_deleted()
    {
        // 1. Arrange
        CompanyAdministrativeDayPolicy::create([
            'company_id' => $this->company->id,
            'is_enabled' => true,
            'days_per_cycle' => 3.0,
            'unit' => 'full_day',
            'cycle_type' => 'annual',
        ]);

        app(AdministrativeDayProvisioner::class)->provisionCompany($this->company);

        $attendanceService = app(AdministrativeDayAttendanceService::class);

        $record = $attendanceService->storeOrUpdateAttendance($this->employee, [
            'date' => '2026-05-15',
            'status' => 'ausente',
            'is_justified' => true,
            'justification_type' => 'administrative_day',
        ]);

        // Verify balance reduced to 2
        $this->assertDatabaseHas('employee_administrative_day_balances', [
            'employee_id' => $this->employee->id,
            'available_units' => 2.0,
        ]);

        // 2. Act
        $attendanceService->deleteAttendance($this->employee, $record);

        // 3. Assert
        $this->assertDatabaseHas('employee_administrative_day_balances', [
            'employee_id' => $this->employee->id,
            'available_units' => 3.0,
            'used_units' => 0.0,
        ]);

        $this->assertDatabaseMissing('administrative_day_usages', [
            'attendance_record_id' => $record->id,
        ]);
    }

    /** @test */
    public function it_does_not_discount_administrative_days_from_payroll_worked_days()
    {
        // 1. Arrange
        CompanyAdministrativeDayPolicy::create([
            'company_id' => $this->company->id,
            'is_enabled' => true,
            'days_per_cycle' => 3.0,
            'unit' => 'full_day',
            'cycle_type' => 'annual',
        ]);

        app(AdministrativeDayProvisioner::class)->provisionCompany($this->company);

        $attendanceService = app(AdministrativeDayAttendanceService::class);

        // Register one administrative day (paid permission)
        $attendanceService->storeOrUpdateAttendance($this->employee, [
            'date' => '2026-05-15',
            'status' => 'ausente',
            'is_justified' => true,
            'justification_type' => 'administrative_day',
        ]);

        // Register one unjustified absence (discountable)
        $attendanceService->storeOrUpdateAttendance($this->employee, [
            'date' => '2026-05-16',
            'status' => 'ausente',
            'is_justified' => false,
            'justification_type' => 'inasistencia_injustificada',
        ]);

        $period = PayrollPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2026,
            'month' => 5,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'status' => 'open',
        ]);

        $payrollService = new ProcessEmployeePayroll;

        // 2. Act
        $result = $payrollService->execute($this->employee->id, $period->id);

        // 3. Assert
        // Standard worked days = 30. We should only subtract the unjustified absence (1 day).
        // The administrative day should NOT be subtracted.
        // So worked_days = 29.
        $this->assertEquals(29, $result->worked_days);
        
        // Base salary = 1,000,000. Proportional base salary should be (1,000,000 / 30) * 29
        $expectedSalary = (int) round((1000000 / 30) * 29);
        $this->assertEquals($expectedSalary, $result->proportional_salary);
    }

    /** @test */
    public function employee_can_request_administrative_day()
    {
        \Spatie\Permission\Models\Role::findOrCreate('employee', 'web');
        $this->user->assignRole('employee');

        CompanyAdministrativeDayPolicy::create([
            'company_id' => $this->company->id,
            'is_enabled' => true,
            'days_per_cycle' => 3.0,
            'unit' => 'full_day',
            'cycle_type' => 'annual',
        ]);

        app(AdministrativeDayProvisioner::class)->provisionCompany($this->company);

        $response = $this->actingAs($this->user)
            ->post(route('employee.requests.store'), [
                'requested_date' => now()->addDay()->format('Y-m-d'),
                'notes' => 'Trámite urgente',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('administrative_day_requests', [
            'employee_id' => $this->employee->id,
            'status' => 'pending',
            'notes' => 'Trámite urgente',
        ]);
    }

    /** @test */
    public function employee_cannot_request_if_insufficient_balance()
    {
        \Spatie\Permission\Models\Role::findOrCreate('employee', 'web');
        $this->user->assignRole('employee');

        CompanyAdministrativeDayPolicy::create([
            'company_id' => $this->company->id,
            'is_enabled' => true,
            'days_per_cycle' => 0.0, // no balance
            'unit' => 'full_day',
            'cycle_type' => 'annual',
        ]);

        app(AdministrativeDayProvisioner::class)->provisionCompany($this->company);

        $response = $this->actingAs($this->user)
            ->post(route('employee.requests.store'), [
                'requested_date' => now()->addDay()->format('Y-m-d'),
                'notes' => 'Trámite urgente',
            ]);

        $response->assertSessionHasErrors('requested_date');
    }

    /** @test */
    public function employee_can_cancel_pending_request()
    {
        \Spatie\Permission\Models\Role::findOrCreate('employee', 'web');
        $this->user->assignRole('employee');

        $req = \Modules\Employees\Models\AdministrativeDayRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'requested_date' => now()->addDay()->format('Y-m-d'),
            'status' => 'pending',
            'notes' => 'Test cancel',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('employee.requests.cancel', $req));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $this->assertEquals('cancelled', $req->fresh()->status);
    }

    /** @test */
    public function admin_can_approve_request()
    {
        \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'status' => true,
        ]);
        $adminUser->company_id = $this->company->id;
        $adminUser->save();
        $adminUser->assignRole('admin');

        CompanyAdministrativeDayPolicy::create([
            'company_id' => $this->company->id,
            'is_enabled' => true,
            'days_per_cycle' => 3.0,
            'unit' => 'full_day',
            'cycle_type' => 'annual',
        ]);

        app(AdministrativeDayProvisioner::class)->provisionCompany($this->company);

        $req = \Modules\Employees\Models\AdministrativeDayRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'requested_date' => now()->addDay()->format('Y-m-d'),
            'status' => 'pending',
            'notes' => 'Aprobar esto',
        ]);

        $response = $this->actingAs($adminUser)
            ->post(route('companies.requests.approve', [$this->company, $req]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Restore tenant context bypass after HTTP request has run and cleared it
        app(\App\Support\Tenancy\TenantContext::class)->bypass();

        $this->assertEquals('approved', $req->fresh()->status);
        $this->assertEquals($adminUser->id, $req->fresh()->resolved_by);

        // Verify balance deducted
        $balance = EmployeeAdministrativeDayBalance::where('employee_id', $this->employee->id)->first();
        $this->assertEquals(2.0, $balance->available_units);
        $this->assertEquals(1.0, $balance->used_units);

        // Verify attendance record created and has correct date format
        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'justification_type' => 'administrative_day',
        ]);
        
        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertEquals($req->requested_date->format('Y-m-d'), $record->date->format('Y-m-d'));
    }

    /** @test */
    public function admin_can_reject_request_with_reason()
    {
        \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'status' => true,
        ]);
        $adminUser->company_id = $this->company->id;
        $adminUser->save();
        $adminUser->assignRole('admin');

        CompanyAdministrativeDayPolicy::create([
            'company_id' => $this->company->id,
            'is_enabled' => true,
            'days_per_cycle' => 3.0,
            'unit' => 'full_day',
            'cycle_type' => 'annual',
        ]);

        app(AdministrativeDayProvisioner::class)->provisionCompany($this->company);

        $req = \Modules\Employees\Models\AdministrativeDayRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'requested_date' => now()->addDay()->format('Y-m-d'),
            'status' => 'pending',
            'notes' => 'Rechazar esto',
        ]);

        $response = $this->actingAs($adminUser)
            ->post(route('companies.requests.reject', [$this->company, $req]), [
                'rejection_reason' => 'No hay personal suficiente',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Restore tenant context bypass after HTTP request has run and cleared it
        app(\App\Support\Tenancy\TenantContext::class)->bypass();

        $this->assertEquals('rejected', $req->fresh()->status);
        $this->assertEquals('No hay personal suficiente', $req->fresh()->rejection_reason);

        // Verify balance NOT deducted
        $balance = EmployeeAdministrativeDayBalance::where('employee_id', $this->employee->id)->first();
        $this->assertEquals(3.0, $balance->available_units);
        $this->assertEquals(0.0, $balance->used_units);
    }
}
