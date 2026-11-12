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

    public function test_tenant_can_own_multiple_companies_and_users(): void
    {
        $tenant = Tenant::create([
            'name' => 'Grupo Empresarial Andino',
            'rut' => '76123456-7',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->assertSame('grupo-empresarial-andino', $tenant->slug);
        $this->assertTrue($tenant->isActive());

        $companyA = Company::create([
            'tenant_id' => $tenant->id,
            'name' => 'Andina Retail SpA',
            'rut' => '76111111-1',
        ]);

        $companyB = Company::create([
            'tenant_id' => $tenant->id,
            'name' => 'Andina Logística SpA',
            'rut' => '76222222-2',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertCount(2, $tenant->companies);
        $this->assertCount(1, $tenant->users);
        $this->assertSame($tenant->id, $companyA->tenant->id);
        $this->assertSame($tenant->id, $companyB->tenant->id);
        $this->assertSame($tenant->id, $user->tenant->id);
    }

    public function test_tenant_column_scoping_isolates_records_by_organization_tenant(): void
    {
        $tenantA = Tenant::create(['name' => 'Holding A', 'slug' => 'holding-a']);
        $tenantB = Tenant::create(['name' => 'Holding B', 'slug' => 'holding-b']);

        app(TenantContext::class)->bypass();

        TenantScopedCompanyStub::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Empresa Holding A',
            'slug' => 'empresa-holding-a',
            'rut' => '77111111-1',
        ]);

        TenantScopedCompanyStub::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Empresa Holding B',
            'slug' => 'empresa-holding-b',
            'rut' => '77222222-2',
        ]);

        app(TenantContext::class)->bypass(false);
        app(TenantContext::class)->setTenantId($tenantA->id);

        $visible = TenantScopedCompanyStub::query()->pluck('name')->all();

        $this->assertSame(['Empresa Holding A'], $visible);
    }

    public function test_tenant_context_resolves_tenant_id_from_active_company(): void
    {
        $tenant = Tenant::create(['name' => 'Estudio Contable Sur', 'slug' => 'estudio-sur']);

        $company = Company::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente Uno Ltda',
            'rut' => '78111111-1',
        ]);

        $context = app(TenantContext::class);
        $context->setCompanyId($company->id);

        $this->assertSame($tenant->id, $context->tenantId());
        $this->assertTrue($context->hasTenant('tenant_id'));
    }
}

