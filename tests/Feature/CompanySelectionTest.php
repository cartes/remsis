<?php

namespace Tests\Feature;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Users\Models\User;
use Tests\TestCase;

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

    public function test_accessing_company_base_url_redirects_to_dashboard()
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $company = Company::create(['razon_social' => 'Show Company', 'rut' => '5-5', 'name' => 'Show Company']);
        $user->companies()->attach($company);

        $this->actingAs($user);

        $response = $this->get(route('companies.show', $company));

        $response->assertRedirect(route('companies.dashboard', $company));
    }

    public function test_company_has_admin_detects_administrators_without_employee_record()
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $company = Company::create(['razon_social' => 'No Employee Admin Co', 'rut' => '6-6', 'name' => 'No Employee Admin Co']);

        $this->assertFalse($company->hasAdmin());
        $this->assertSame(0, $company->adminCount());

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $company->administrators()->attach($admin->id, ['role' => 'admin', 'is_external' => true]);

        $this->assertTrue($company->fresh()->hasAdmin());
        $this->assertSame(1, $company->fresh()->adminCount());
    }

    public function test_dashboard_renders_without_admin_modal_when_company_has_external_admin()
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $company = Company::create(['razon_social' => 'External Admin Co', 'rut' => '7-7', 'name' => 'External Admin Co']);

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $company->administrators()->attach($admin->id, ['role' => 'admin', 'is_external' => true]);

        $this->actingAs($admin);

        $response = $this->get(route('companies.dashboard', $company));

        $response->assertOk();
        $response->assertViewHas('hasAdmin', true);
    }
}
