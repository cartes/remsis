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

            if ($tenantContext->isBypassed()) {
                return;
            }

            if (! $tenantContext->hasTenant()) {
                // Modo estricto: Si no hay tenant y no se ha omitido el contexto,
                // prevenimos la consulta de todos los registros forzando un resultado vacío.
                $builder->whereRaw('1 = 0');
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

            if ($tenantContext->isBypassed() || ! empty($model->{$tenantColumn})) {
                return;
            }

            if (! $tenantContext->hasTenant()) {
                // Modo estricto: Si no hay tenant y no se ha omitido el contexto,
                // lanzamos una excepción para evitar la creación de registros huérfanos.
                throw new \RuntimeException('No se puede crear el modelo sin un tenant activo.');
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
