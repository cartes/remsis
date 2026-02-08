<?php

namespace Modules\Companies\Tests\Feature;

use Tests\TestCase;
use Modules\Users\Models\User;
use Modules\Employees\Models\Employee;
use Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_employees_by_name()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $employeeUser = User::factory()->create(['name' => 'John Doe Searchable']);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'company_id' => $company->id
        ]);

        $this->actingAs($user);

        $response = $this->getJson(route('companies.employees.search', ['company' => $company, 'query' => 'John']));

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'John Doe Searchable']);
    }

    public function test_search_requires_at_least_3_characters()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson(route('companies.employees.search', ['company' => $company, 'query' => 'Jo']));

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }
}
