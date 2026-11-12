<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Modules\Companies\Models\Company;
use Modules\Core\Models\Tenant;
use Modules\Employees\Models\Employee;

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

            if (! empty($model->{$tenantColumn})) {
                if ($tenantContext->hasTenant($tenantColumn) && (int) $model->{$tenantColumn} !== (int) $tenantContext->resolveIdForColumn($tenantColumn)) {
                    throw new \RuntimeException('No se puede asociar el modelo a un tenant distinto al activo.');
                }
            } else {
                if (! $tenantContext->hasTenant($tenantColumn)) {
                    // Si el modelo tiene relación con un empleado (ej: AttendanceRecord, EmployeeDocument, EmployeeLog, EmployeeItem)
                    // inferimos company_id y tenant_id desde el colaborador antes de fallar.
                    if (! empty($model->employee_id)) {
                        $emp = Employee::withoutGlobalScopes()->find($model->employee_id);
                        if ($emp) {
                            $model->{$tenantColumn} = $tenantColumn === 'tenant_id' ? $emp->tenant_id : $emp->company_id;
                            if (empty($model->tenant_id) && ! empty($emp->tenant_id) && Schema::hasColumn($model->getTable(), 'tenant_id')) {
                                $model->tenant_id = $emp->tenant_id;
                            }
                            if (empty($model->company_id) && ! empty($emp->company_id) && Schema::hasColumn($model->getTable(), 'company_id')) {
                                $model->company_id = $emp->company_id;
                            }
                        }
                    }

                    if (empty($model->{$tenantColumn})) {
                        // Modo estricto: Si no hay tenant y no se ha omitido el contexto,
                        // lanzamos una excepción para evitar la creación de registros huérfanos.
                        throw new \RuntimeException('No se puede crear el modelo sin un tenant activo.');
                    }
                } else {
                    $model->{$tenantColumn} = $tenantContext->resolveIdForColumn($tenantColumn);
                }
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

            // Si el modelo soporta además company_id y está vacío, lo propagamos desde el contexto
            if (
                $tenantColumn !== 'company_id'
                && empty($model->company_id)
                && $tenantContext->companyId() !== null
                && Schema::hasColumn($model->getTable(), 'company_id')
            ) {
                $model->company_id = $tenantContext->companyId();
            }

            if (
                Schema::hasColumn($model->getTable(), 'tenant_id')
                && ! empty($model->tenant_id)
                && $tenantContext->tenantId() !== null
                && (int) $model->tenant_id !== (int) $tenantContext->tenantId()
            ) {
                throw new \RuntimeException('No se puede asociar el modelo a un tenant_id distinto al activo.');
            }
        });

        static::updating(function ($model) {
            $tenantContext = app(TenantContext::class);
            $tenantColumn = static::getTenantColumn();

            if ($tenantContext->isBypassed()) {
                return;
            }

            if (! $tenantContext->hasTenant($tenantColumn)) {
                // Modo estricto: Si no hay tenant activo y no está en bypass, impedir actualizar el modelo
                throw new \RuntimeException('No se puede actualizar el modelo sin un tenant activo.');
            }

            $activeId = $tenantContext->resolveIdForColumn($tenantColumn);
            $originalId = $model->getOriginal($tenantColumn);

            // Verificar que el registro original pertenecía al tenant activo
            if ($originalId !== null && (int) $originalId !== (int) $activeId) {
                throw new \RuntimeException('No se puede actualizar un modelo perteneciente a otro tenant.');
            }

            // Prohibir reasignar el tenantColumn a otro tenant
            if ($model->isDirty($tenantColumn) && (int) $model->{$tenantColumn} !== (int) $activeId) {
                throw new \RuntimeException('No se puede reasignar el modelo a otro tenant.');
            }

            if (
                Schema::hasColumn($model->getTable(), 'tenant_id')
                && $tenantContext->tenantId() !== null
            ) {
                $originalTenantId = $model->getOriginal('tenant_id');
                if ($originalTenantId !== null && (int) $originalTenantId !== (int) $tenantContext->tenantId()) {
                    throw new \RuntimeException('No se puede actualizar un modelo perteneciente a otro tenant.');
                }
                if ($model->isDirty('tenant_id') && (int) $model->tenant_id !== (int) $tenantContext->tenantId()) {
                    throw new \RuntimeException('No se puede reasignar el tenant_id a otro tenant.');
                }
            }

            if (
                Schema::hasColumn($model->getTable(), 'company_id')
                && $tenantContext->companyId() !== null
            ) {
                $originalCompanyId = $model->getOriginal('company_id');
                if ($originalCompanyId !== null && (int) $originalCompanyId !== (int) $tenantContext->companyId()) {
                    throw new \RuntimeException('No se puede actualizar un modelo perteneciente a otra empresa.');
                }
                if ($model->isDirty('company_id') && (int) $model->company_id !== (int) $tenantContext->companyId()) {
                    throw new \RuntimeException('No se puede reasignar el company_id a otra empresa.');
                }
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
