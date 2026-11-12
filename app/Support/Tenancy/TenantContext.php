<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

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
    }

    public function tenantId(): ?int
    {
        if ($this->tenantId !== null) {
            return $this->tenantId;
        }

        if ($this->companyId !== null) {
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
    }

    public function companyId(): ?int
    {
        return $this->companyId;
    }

    public function hasTenant(?string $column = null): bool
    {
        if ($column === 'tenant_id') {
            return $this->tenantId() !== null;
        }

        if ($column === 'company_id') {
            return $this->companyId !== null;
        }

        return $this->companyId !== null || $this->tenantId !== null;
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

        if (! empty($user->tenant_id)) {
            $this->setTenantId((int) $user->tenant_id);
        }

        $selectedCompanyId = session('selected_company_id');

        if ($selectedCompanyId) {
            $this->setCompanyId((int) $selectedCompanyId);
        } else {
            $fallbackCompanyId = $user->company_id ?? $user->employee?->company_id;
            $this->setCompanyId($fallbackCompanyId !== null ? (int) $fallbackCompanyId : null);
        }
    }

    public function clear(): void
    {
        $this->tenantId = null;
        $this->companyId = null;
        $this->bypassed = false;
    }
}
