<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MultiCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_company_user_can_access_multiple_companies(): void
    {
        // 1. Setup roles
        $multiRole = Role::create(['name' => 'multi-company', 'guard_name' => 'web']);

        // 2. Create user
        $user = User::factory()->create();
        $user->assignRole($multiRole);

        // 3. Create companies
        $company1 = Company::create(['razon_social' => 'Empresa 1', 'rut' => '1-1', 'name' => 'Empresa 1']);
        $company2 = Company::create(['razon_social' => 'Empresa 2', 'rut' => '2-2', 'name' => 'Empresa 2']);
        $company3 = Company::create(['razon_social' => 'Empresa 3', 'rut' => '3-3', 'name' => 'Empresa 3']);

        // 4. Associate user with companies via pivot table
        $user->companies()->attach([$company1->id, $company2->id]);

        // 5. Authenticate and check redirection
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('companies.select'));

        // 6. Check companies index
        $response = $this->actingAs($user)->get(route('companies.index'));
        $response->assertStatus(200);
        $response->assertSee('Empresa 1');
        $response->assertSee('Empresa 2');
        $response->assertDontSee('Empresa 3');
    }
}
