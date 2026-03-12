<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantContext = app(TenantContext::class);

            if ($tenantContext->isBypassed() || ! $tenantContext->hasTenant()) {
                return;
            }

            $builder->where(
                $builder->qualifyColumn(static::getTenantColumn()),
                $tenantContext->companyId()
            );
        });

        static::creating(function ($model) {
            $tenantContext = app(TenantContext::class);
            $tenantColumn = static::getTenantColumn();

            if ($tenantContext->isBypassed() || ! $tenantContext->hasTenant() || ! empty($model->{$tenantColumn})) {
                return;
            }

            $model->{$tenantColumn} = $tenantContext->companyId();
        });
    }

    public function scopeForTenant(Builder $query, int $companyId): Builder
    {
        return $query->withoutGlobalScope('tenant')
            ->where($query->qualifyColumn(static::getTenantColumn()), $companyId);
    }

    public static function getTenantColumn(): string
    {
        return defined('static::TENANT_COLUMN') ? static::TENANT_COLUMN : 'company_id';
    }
}
