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

            if (empty($model->{$tenantColumn})) {
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
