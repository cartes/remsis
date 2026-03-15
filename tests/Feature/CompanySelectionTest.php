<?php

namespace Tests\Feature;

use Modules\Users\Models\User;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Support\Tenancy\TenantContext;

class CompanySelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_multiple_companies_is_redirected_to_selector()
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        // Company 1 (Primary)
        $company1 = Company::create(['razon_social' => 'Company 1', 'rut' => '1-1', 'name' => 'Company 1']);
        Employee::create([
            'user_id' => $user->id,
            'company_id' => $company1->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $user->email,
        ]);

        // Company 2 (Associated via pivot)
        $company2 = Company::create(['razon_social' => 'Company 2', 'rut' => '2-2', 'name' => 'Company 2']);
        $user->companies()->attach($company2);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('companies.select'));
    }

    public function test_selecting_company_persists_in_session_and_context()
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $company = Company::create(['razon_social' => 'Target Company', 'rut' => '3-3', 'name' => 'Target Company']);
        $user->companies()->attach($company);

        $this->actingAs($user);

        $response = $this->post(route('companies.selected', $company));

        $response->assertRedirect(route('companies.dashboard', $company));
        $this->assertEquals($company->id, session('selected_company_id'));

        // Verify TenantContext picks it up
        $context = app(TenantContext::class);
        $context->initializeForUser($user);
        $this->assertEquals($company->id, $context->companyId());
    }

    public function test_user_with_single_company_is_redirected_to_dashboard_directly()
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $company = Company::create(['razon_social' => 'Single Company', 'rut' => '4-4', 'name' => 'Single Company']);
        Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $user->email,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Should bypass selector and go directly to dashboard
        $response->assertRedirect(route('companies.dashboard', $company));
        $this->assertEquals($company->id, session('selected_company_id'));
    }
}
