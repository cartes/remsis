<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use Illuminate\Support\Facades\Schema;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;

class TenantContext
{
    protected ?int $tenantId = null;

    protected ?int $companyId = null;

    protected bool $bypassed = false;

    public function setTenantId(?int $tenantId): void
    {
        $this->tenantId = $tenantId;

        if ($tenantId === null) {
            $this->companyId = null;

            return;
        }

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'tenant_id')) {
            $resolvedCompanyId = Company::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->value('id');

            $this->companyId = $resolvedCompanyId !== null ? (int) $resolvedCompanyId : null;
        }
    }

    public function tenantId(): ?int
    {
        if ($this->tenantId !== null) {
            return $this->tenantId;
        }

        if ($this->companyId !== null && Schema::hasTable('companies') && Schema::hasColumn('companies', 'tenant_id')) {
            $resolvedTenantId = Company::query()
                ->withoutGlobalScopes()
                ->whereKey($this->companyId)
                ->value('tenant_id');

            return $resolvedTenantId !== null ? (int) $resolvedTenantId : null;
        }

        return null;
    }

    public function setCompanyId(?int $companyId): void
    {
        $this->companyId = $companyId;

        if ($companyId === null) {
            $this->tenantId = null;

            return;
        }

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'tenant_id')) {
            $resolvedTenantId = Company::query()
                ->withoutGlobalScopes()
                ->whereKey($companyId)
                ->value('tenant_id');

            $this->tenantId = $resolvedTenantId !== null ? (int) $resolvedTenantId : null;
        }
    }

    public function companyId(): ?int
    {
        if ($this->companyId !== null) {
            return $this->companyId;
        }

        if ($this->tenantId !== null && Schema::hasTable('companies') && Schema::hasColumn('companies', 'tenant_id')) {
            $resolvedCompanyId = Company::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $this->tenantId)
                ->value('id');

            return $resolvedCompanyId !== null ? (int) $resolvedCompanyId : null;
        }

        return null;
    }

    public function hasTenant(?string $column = null): bool
    {
        if ($column === 'tenant_id') {
            return $this->tenantId() !== null;
        }

        if ($column === 'company_id') {
            return $this->companyId() !== null;
        }

        return $this->companyId() !== null || $this->tenantId() !== null;
    }

    public function resolveIdForColumn(string $column): ?int
    {
        return $column === 'tenant_id'
            ? $this->tenantId()
            : $this->companyId();
    }

    public function bypass(bool $bypassed = true): void
    {
        $this->bypassed = $bypassed;
    }

    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    public function initializeForUser(?User $user): void
    {
        $this->clear();

        if (! $user) {
            return;
        }

        if ($user->hasRole('super-admin')) {
            $this->bypass();

            return;
        }

        $accessibleCompanyIds = $user->getAllCompanies()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $selectedCompanyId = session('selected_company_id');

        if ($selectedCompanyId !== null) {
            $selectedCompanyId = (int) $selectedCompanyId;

            if (! empty($accessibleCompanyIds)) {
                if (in_array($selectedCompanyId, $accessibleCompanyIds, true)) {
                    $this->setCompanyId($selectedCompanyId);

                    return;
                }

                // Si el usuario tiene membresías definidas y la empresa en sesión no está autorizada,
                // limpiamos la selección inválida de la sesión.
                session()->forget(['selected_company_id', 'selected_tenant_id']);

                if (count($accessibleCompanyIds) === 1) {
                    $this->setCompanyId($accessibleCompanyIds[0]);
                }

                return;
            }

            $this->setCompanyId($selectedCompanyId);

            return;
        }

        $fallbackCompanyId = $user->company_id
            ?? $user->employee?->company_id
            ?? (count($accessibleCompanyIds) === 1 ? $accessibleCompanyIds[0] : null);

        if ($fallbackCompanyId !== null) {
            $this->setCompanyId((int) $fallbackCompanyId);

            return;
        }

        if (! empty($user->tenant_id)) {
            $this->setTenantId((int) $user->tenant_id);
        }
    }

    public function clear(): void
    {
        $this->tenantId = null;
        $this->companyId = null;
        $this->bypassed = false;
    }
}
