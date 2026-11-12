<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Companies\Models\Company;
use Modules\Core\Models\Tenant;
use Modules\Core\Traits\BelongsToTenant;
use Modules\Users\Models\User;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantScopedCompanyStub extends Model
{
    use BelongsToTenant;

    public const TENANT_COLUMN = 'tenant_id';

    protected $table = 'companies';

    protected $guarded = [];
}

class TenantModelTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();

        parent::tearDown();
    }

    public function test_company_automatically_creates_one_to_one_tenant_with_normalized_rut(): void
    {
        $company = Company::create([
            'name' => 'Andina Retail SpA',
            'razon_social' => 'Andina Retail SpA',
            'rut' => '76.123.456-k',
        ]);

        $this->assertNotNull($company->tenant_id);
        $this->assertSame('76123456-K', $company->rut);

        $tenant = $company->tenant;
        $this->assertNotNull($tenant);
        $this->assertSame('76123456-K', $tenant->rut);
        $this->assertSame('andina-retail-spa', $tenant->slug);
        $this->assertTrue($tenant->isActive());
        $this->assertSame($company->id, $tenant->company->id);
    }

    public function test_tenant_cannot_be_shared_by_multiple_companies(): void
    {
        $tenant = Tenant::create([
            'name' => 'Andina Retail SpA',
            'rut' => '76.111.111-1',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $companyA = Company::create([
            'tenant_id' => $tenant->id,
            'name' => 'Andina Retail SpA',
            'rut' => '76111111-1',
        ]);

        $this->assertSame($tenant->id, $companyA->tenant_id);
        $this->assertSame($companyA->id, $tenant->company->id);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Un tenant solo puede pertenecer a una única empresa');

        Company::create([
            'tenant_id' => $tenant->id,
            'name' => 'Otra Empresa SpA',
            'rut' => '76222222-2',
        ]);
    }

    public function test_tenant_column_scoping_isolates_records_by_organization_tenant(): void
    {
        $tenantA = Tenant::create(['name' => 'Empresa A', 'rut' => '77111111-1', 'slug' => 'empresa-a']);
        $tenantB = Tenant::create(['name' => 'Empresa B', 'rut' => '77222222-2', 'slug' => 'empresa-b']);

        app(TenantContext::class)->bypass();

        TenantScopedCompanyStub::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Empresa A',
            'slug' => 'empresa-a',
            'rut' => '77111111-1',
        ]);

        TenantScopedCompanyStub::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Empresa B',
            'slug' => 'empresa-b',
            'rut' => '77222222-2',
        ]);

        app(TenantContext::class)->bypass(false);
        app(TenantContext::class)->setTenantId($tenantA->id);

        $visible = TenantScopedCompanyStub::query()->pluck('name')->all();

        $this->assertSame(['Empresa A'], $visible);
    }

    public function test_tenant_context_resolves_and_synchronizes_tenant_and_company_one_to_one(): void
    {
        $companyA = Company::create([
            'name' => 'Cliente Uno Ltda',
            'rut' => '78111111-1',
        ]);

        $companyB = Company::create([
            'name' => 'Cliente Dos Ltda',
            'rut' => '78222222-2',
        ]);

        $context = app(TenantContext::class);
        $context->setCompanyId($companyA->id);

        $this->assertSame($companyA->id, $context->companyId());
        $this->assertSame($companyA->tenant_id, $context->tenantId());
        $this->assertTrue($context->hasTenant('tenant_id'));

        // Al cambiar de empresa activa, el tenant_id también se actualiza en relación 1:1
        $context->setCompanyId($companyB->id);
        $this->assertSame($companyB->id, $context->companyId());
        $this->assertSame($companyB->tenant_id, $context->tenantId());
    }

    public function test_tenant_context_rejects_unauthorized_session_company_for_user_with_memberships(): void
    {
        Role::firstOrCreate(['name' => 'contador', 'guard_name' => 'web']);

        $companyAuthorized = Company::create([
            'name' => 'Empresa Autorizada SpA',
            'rut' => '76111111-1',
        ]);

        $companyForbidden = Company::create([
            'name' => 'Empresa Ajena SpA',
            'rut' => '76222222-2',
        ]);

        $user = User::factory()->create();
        $user->assignRole('contador');
        $user->companies()->attach($companyAuthorized->id);

        // Simulamos un intento de fijar en sesión una empresa no autorizada
        session(['selected_company_id' => $companyForbidden->id]);

        $context = app(TenantContext::class);
        $context->initializeForUser($user);

        // Debe ignorar la empresa ajena y resolver únicamente la empresa autorizada
        $this->assertSame($companyAuthorized->id, $context->companyId());
        $this->assertSame($companyAuthorized->tenant_id, $context->tenantId());
        $this->assertNull(session('selected_company_id'));
    }
}

