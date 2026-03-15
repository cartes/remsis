<?php

namespace Tests\Feature\Auth;

use Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        
        $company = \Modules\Companies\Models\Company::create(['razon_social' => 'Test Company', 'rut' => '1-9', 'name' => 'Test Company']);
        \Modules\Employees\Models\Employee::create([
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

        $this->assertAuthenticated();
        $response->assertRedirect(route('companies.dashboard', $company, false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_dashboard_redirects_to_company_dashboard(): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        
        $company = \Modules\Companies\Models\Company::create(['razon_social' => 'Test Company', 'rut' => '1-10', 'name' => 'Test Company']);
        \Modules\Employees\Models\Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $user->email,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('companies.dashboard', $company, false));
    }
}
