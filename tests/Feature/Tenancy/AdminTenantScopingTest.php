<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_super_admin_only_sees_its_company_in_companies_api(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $companyA = Company::create([
            'name' => 'Empresa A',
            'razon_social' => 'Empresa A SpA',
            'rut' => '11111111-1',
        ]);

        $companyB = Company::create([
            'name' => 'Empresa B',
            'razon_social' => 'Empresa B SpA',
            'rut' => '22222222-2',
        ]);

        $admin = User::factory()->create([
            'company_id' => $companyA->id,
        ]);

        $admin->assignRole($adminRole);

        $response = $this->actingAs($admin)->getJson(route('admin.companies.index'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $companyA->id);
    }

    public function test_non_super_admin_cannot_attach_user_to_another_company(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $companyA = Company::create([
            'name' => 'Empresa A',
            'razon_social' => 'Empresa A SpA',
            'rut' => '11111111-1',
        ]);

        $companyB = Company::create([
            'name' => 'Empresa B',
            'razon_social' => 'Empresa B SpA',
            'rut' => '22222222-2',
        ]);

        $admin = User::factory()->create([
            'company_id' => $companyA->id,
        ]);
        $admin->assignRole($adminRole);

        $managedUser = User::factory()->create();
        $managedUser->assignRole($adminRole);

        Employee::create([
            'user_id' => $managedUser->id,
            'company_id' => $companyA->id,
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('users.attach-company', $managedUser),
            ['company_id' => $companyB->id]
        );

        $response->assertForbidden();
    }
}
