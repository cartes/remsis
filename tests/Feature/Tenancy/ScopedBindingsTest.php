<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CostCenter;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScopedBindingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cost_center_binding_is_scoped_to_the_selected_company(): void
    {
        $role = Role::create([
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

        $user = User::factory()->create([
            'company_id' => $companyA->id,
        ]);

        $user->assignRole($role);

        $costCenter = CostCenter::create([
            'company_id' => $companyB->id,
            'code' => 'B001',
            'name' => 'Centro B',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(
            route('companies.cost-centers.update', [$companyA, $costCenter]),
            [
                'code' => 'A001',
                'name' => 'Centro A',
                'status' => 'active',
            ]
        );

        $response->assertNotFound();
    }
}
