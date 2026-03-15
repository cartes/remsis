<?php

namespace App\Support\Tenancy;

use Modules\Users\Models\User;

class TenantContext
{
    protected ?int $companyId = null;

    protected bool $bypassed = false;

    public function setCompanyId(?int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function companyId(): ?int
    {
        return $this->companyId;
    }

    public function hasTenant(): bool
    {
        return $this->companyId !== null;
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

        $selectedCompanyId = session('selected_company_id');
        
        if ($selectedCompanyId) {
            $this->setCompanyId($selectedCompanyId);
        } else {
            $this->setCompanyId($user->company_id ?? $user->employee?->company_id);
        }
    }

    public function clear(): void
    {
        $this->companyId = null;
        $this->bypassed = false;
    }
}
