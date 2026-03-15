<?php

namespace Tests\Feature;

use Modules\Users\Models\User;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Support\Tenancy\TenantContext;

class GlobalTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure roles exist
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_models_are_automatically_scoped_by_selected_company_in_session()
    {
        // 1. Create two companies
        $company1 = Company::create(['razon_social' => 'Company One', 'rut' => '1-1', 'name' => 'Company One']);
        $company2 = Company::create(['razon_social' => 'Company Two', 'rut' => '2-2', 'name' => 'Company Two']);

        // 2. Create data for both companies
        // Company 1 data
        Employee::create([
            'user_id' => User::factory()->create()->id,
            'company_id' => $company1->id,
            'first_name' => 'John',
            'last_name' => 'C1',
            'rut' => '11-1',
            'email' => 'john@company1.com'
        ]);

        // Company 2 data
        Employee::create([
            'user_id' => User::factory()->create()->id,
            'company_id' => $company2->id,
            'first_name' => 'Jane',
            'last_name' => 'C2',
            'rut' => '22-2',
            'email' => 'jane@company2.com'
        ]);

        // 3. Create a user and select Company 1 in session
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        // This is where the magic should happen: TenantContext reads from session
        session(['selected_company_id' => $company1->id]);
        $this->actingAs($user);
        app(TenantContext::class)->initializeForUser($user);

        // 4. Verify filtering
        // We expect only 1 employee (the one from Company 1)
        $this->assertEquals(1, Employee::count());
        $this->assertEquals('John', Employee::first()->first_name);

        // 5. Switch company in session and verify again
        session(['selected_company_id' => $company2->id]);
        
        // Context needs to be re-initialized in the test environment if not handled by middleware
        app(TenantContext::class)->initializeForUser($user);

        $this->assertEquals(1, Employee::count());
        $this->assertEquals('Jane', Employee::first()->first_name);
    }

    public function test_new_records_are_automatically_assigned_to_the_current_tenant()
    {
        $company = Company::create(['razon_social' => 'Auto Tenant Corp', 'rut' => '3-3', 'name' => 'Auto Tenant Corp']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        session(['selected_company_id' => $company->id]);
        $this->actingAs($user);
        app(TenantContext::class)->initializeForUser($user);

        // Create employee without explicitly setting company_id
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'first_name' => 'Auto',
            'last_name' => 'Tenant',
            'rut' => '33-3',
            'email' => 'auto@tenant.com'
        ]);

        $this->assertEquals($company->id, $employee->company_id);
    }

    public function test_payroll_models_also_respect_the_tenant_scope()
    {
        $company1 = Company::create(['razon_social' => 'Payroll C1', 'rut' => '4-4', 'name' => 'Payroll C1']);
        $company2 = Company::create(['razon_social' => 'Payroll C2', 'rut' => '5-5', 'name' => 'Payroll C2']);

        $period1 = PayrollPeriod::create([
            'company_id' => $company1->id,
            'name' => 'Period 1',
            'year' => 2024,
            'month' => 1,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'open'
        ]);

        $period2 = PayrollPeriod::create([
            'company_id' => $company2->id,
            'name' => 'Period 2',
            'year' => 2024,
            'month' => 1,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'open'
        ]);

        $user = User::factory()->create();
        $user->assignRole('admin');

        session(['selected_company_id' => $company1->id]);
        $this->actingAs($user);
        app(TenantContext::class)->initializeForUser($user);

        $this->assertEquals(1, PayrollPeriod::count());
        $this->assertEquals('Period 1', PayrollPeriod::first()->name);
    }
}
