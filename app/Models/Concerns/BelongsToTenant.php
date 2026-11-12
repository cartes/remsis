<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Modules\Companies\Models\Company;
use Modules\Core\Models\Tenant;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantContext = app(TenantContext::class);

            if ($tenantContext->isBypassed()) {
                return;
            }

            $tenantColumn = static::getTenantColumn();

            if (! $tenantContext->hasTenant($tenantColumn)) {
                // Modo estricto: Si no hay tenant y no se ha omitido el contexto,
                // prevenimos la consulta de todos los registros forzando un resultado vacío.
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where(
                $builder->qualifyColumn($tenantColumn),
                $tenantContext->resolveIdForColumn($tenantColumn)
            );
        });

        static::creating(function ($model) {
            $tenantContext = app(TenantContext::class);
            $tenantColumn = static::getTenantColumn();

            if ($tenantContext->isBypassed()) {
                return;
            }

            if (empty($model->{$tenantColumn})) {
                if (! $tenantContext->hasTenant($tenantColumn)) {
                    // Modo estricto: Si no hay tenant y no se ha omitido el contexto,
                    // lanzamos una excepción para evitar la creación de registros huérfanos.
                    throw new \RuntimeException('No se puede crear el modelo sin un tenant activo.');
                }

                $model->{$tenantColumn} = $tenantContext->resolveIdForColumn($tenantColumn);
            }

            // Si el modelo soporta además tenant_id y está vacío, lo propagamos desde el contexto
            if (
                $tenantColumn !== 'tenant_id'
                && empty($model->tenant_id)
                && $tenantContext->tenantId() !== null
                && Schema::hasColumn($model->getTable(), 'tenant_id')
            ) {
                $model->tenant_id = $tenantContext->tenantId();
            }
        });
    }

    public function scopeForTenant(Builder $query, int $tenantOrCompanyId): Builder
    {
        return $query->withoutGlobalScope('tenant')
            ->where($query->qualifyColumn(static::getTenantColumn()), $tenantOrCompanyId);
    }

    public static function getTenantColumn(): string
    {
        return defined('static::TENANT_COLUMN') ? (string) static::TENANT_COLUMN : 'company_id';
    }

    public function tenantOrganization(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function tenantCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
