<?php

namespace Tests\Feature\Companies;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Users\Models\User;
use Tests\TestCase;

class EmployeeImageFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_payroll_returns_public_profile_photo_url(): void
    {
        Storage::fake('public');

        [$company, $actor] = $this->createActorForCompany();

        app(\App\Support\Tenancy\TenantContext::class)->bypass();
        $employeeUser = User::factory()->create([
            'profile_photo' => 'legacy/profile-photos/ana.jpg',
        ]);

        Storage::disk('public')->put('legacy/profile-photos/ana.jpg', 'legacy-photo');

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'company_id' => $company->id,
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
        ]);
        app(\App\Support\Tenancy\TenantContext::class)->bypass(false);

        app(\App\Support\Tenancy\TenantContext::class)->initializeForUser($actor);
        $response = $this->actingAs($actor)->getJson(
            route('companies.employees.payroll', [$company, $employee])
        );

        $response->assertOk()
            ->assertJsonPath('employee.user.profile_photo', 'legacy/profile-photos/ana.jpg')
            ->assertJsonPath('employee.user.profile_photo_url', Storage::url('legacy/profile-photos/ana.jpg'));
    }

    public function test_update_payroll_can_upload_and_replace_profile_photo(): void
    {
        Storage::fake('public');

        [$company, $actor] = $this->createActorForCompany();

        app(\App\Support\Tenancy\TenantContext::class)->bypass();
        $employeeUser = User::factory()->create([
            'email' => 'ana@example.com',
            'profile_photo' => 'legacy/profile-photos/ana-old.jpg',
        ]);

        Storage::disk('public')->put('legacy/profile-photos/ana-old.jpg', 'old-photo');

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'company_id' => $company->id,
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
        ]);
        app(\App\Support\Tenancy\TenantContext::class)->bypass(false);

        app(\App\Support\Tenancy\TenantContext::class)->initializeForUser($actor);
        $response = $this->actingAs($actor)->post(
            route('companies.employees.payroll.update', [$company, $employee]),
            [
                '_method' => 'PUT',
                'first_name' => 'Ana',
                'last_name' => 'Pérez',
                'email' => 'ana@example.com',
                'profile_photo' => UploadedFile::fake()->image('ana.png'),
            ],
            ['Accept' => 'application/json']
        );

        $response->assertOk()
            ->assertJsonPath('employee.user.name', 'Ana Pérez');

        $employeeUser->refresh();

        $this->assertNotNull($employeeUser->profile_photo);
        $this->assertStringStartsWith(
            "companies/{$company->id}/profile-photos/",
            $employeeUser->profile_photo
        );
        $this->assertSame(
            Storage::url($employeeUser->profile_photo),
            $response->json('employee.user.profile_photo_url')
        );
        Storage::disk('public')->assertMissing('legacy/profile-photos/ana-old.jpg');
        Storage::disk('public')->assertExists($employeeUser->profile_photo);
    }

    public function test_update_payroll_without_photo_preserves_existing_profile_photo(): void
    {
        Storage::fake('public');

        [$company, $actor] = $this->createActorForCompany();

        $existingPhoto = 'legacy/profile-photos/existing.jpg';

        app(\App\Support\Tenancy\TenantContext::class)->bypass();
        $employeeUser = User::factory()->create([
            'email' => 'empleado@example.com',
            'profile_photo' => $existingPhoto,
        ]);

        Storage::disk('public')->put($existingPhoto, 'existing-photo');

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'company_id' => $company->id,
            'first_name' => 'Empleado',
            'last_name' => 'Demo',
        ]);
        app(\App\Support\Tenancy\TenantContext::class)->bypass(false);

        app(\App\Support\Tenancy\TenantContext::class)->initializeForUser($actor);
        $response = $this->actingAs($actor)->putJson(
            route('companies.employees.payroll.update', [$company, $employee]),
            [
                'first_name' => 'Empleado',
                'last_name' => 'Actualizado',
                'email' => 'empleado@example.com',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('employee.user.profile_photo', $existingPhoto)
            ->assertJsonPath('employee.user.profile_photo_url', Storage::url($existingPhoto));

        $this->assertSame($existingPhoto, $employeeUser->fresh()->profile_photo);
        Storage::disk('public')->assertExists($existingPhoto);
    }

    public function test_update_payroll_rejects_invalid_profile_photo_files(): void
    {
        Storage::fake('public');

        [$company, $actor] = $this->createActorForCompany();

        app(\App\Support\Tenancy\TenantContext::class)->bypass();
        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'company_id' => $company->id,
        ]);
        app(\App\Support\Tenancy\TenantContext::class)->bypass(false);

        app(\App\Support\Tenancy\TenantContext::class)->initializeForUser($actor);
        $response = $this->actingAs($actor)->post(
            route('companies.employees.payroll.update', [$company, $employee]),
            [
                '_method' => 'PUT',
                'profile_photo' => UploadedFile::fake()->create('avatar.pdf', 100, 'application/pdf'),
            ],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['profile_photo']);
    }

    public function test_payroll_routes_return_not_found_when_employee_belongs_to_another_company(): void
    {
        app(\App\Support\Tenancy\TenantContext::class)->bypass();
        [$companyA, $actor] = $this->createActorForCompany('Empresa A', '11111111-1');

        $companyB = Company::create([
            'name' => 'Empresa B',
            'razon_social' => 'Empresa B SpA',
            'rut' => '22222222-2',
        ]);

        $employee = Employee::create([
            'user_id' => User::factory()->create()->id,
            'company_id' => $companyB->id,
        ]);
        app(\App\Support\Tenancy\TenantContext::class)->bypass(false);

        $this->actingAs($actor)
            ->getJson(route('companies.employees.payroll', [$companyA, $employee]))
            ->assertNotFound();

        $this->actingAs($actor)
            ->putJson(route('companies.employees.payroll.update', [$companyA, $employee]), [
                'first_name' => 'Fuera',
            ])
            ->assertNotFound();
    }

    private function createActorForCompany(
        string $name = 'Empresa Uno',
        string $rut = '12345678-9'
    ): array {
        app(\App\Support\Tenancy\TenantContext::class)->bypass();
        $company = Company::create([
            'name' => $name,
            'razon_social' => "{$name} SpA",
            'rut' => $rut,
        ]);

        $actor = User::factory()->create([
            'company_id' => $company->id,
        ]);
        app(\App\Support\Tenancy\TenantContext::class)->bypass(false);

        return [$company, $actor];
    }
}
