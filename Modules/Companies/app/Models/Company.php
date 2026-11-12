<?php

namespace Modules\Companies\Models;

use App\Rules\Rut;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\AdminPanel\Models\Bank;
use Modules\AdminPanel\Models\Ccaf;
use Modules\AdminPanel\Models\Mutual;
use Modules\AdminPanel\Models\TaxableItem;
use Modules\Core\Models\EconomicActivity;
use Modules\Core\Models\Tenant;
use Modules\Employees\Models\AdministrativeDayRequest;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Users\Models\User;

// use Modules\Companies\Database\Factories\CompanyFactory;

class Company extends Model
{
    const GRATIFICATION_SYSTEM_NONE = 'sin_gratificacion';

    const GRATIFICATION_SYSTEM_ART_47 = 'art_47';

    const GRATIFICATION_SYSTEM_ART_50 = 'art_50';

    const GRATIFICATION_SYSTEM_CONVENTIONAL = 'convencional';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'rut',
        'email',
        'phone',
        'razon_social',
        'nombre_fantasia',
        'giro',
        'direccion',
        'comuna',
        'region',
        'tipo_contribuyente',
        'ccaf',
        'mutual',
        'dia_pago',
        'dia_pago_dia',
        'banco',
        'bank_id',
        'ccaf_id',
        'mutual_id',
        'gratification_system',
        'gratification_months',
        'cuenta_bancaria',
        'representante_nombre',
        'representante_rut',
        'representante_cargo',
        'representante_email',
        'notes',
        'weekly_hours',
        'work_schedule',
        'allows_overtime',
        'slug',
        'economic_activity_id',
        'payment_frequency',
    ];

    protected $casts = [
        'work_schedule' => 'array',
        'gratification_months' => 'integer',
        'allows_overtime' => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, Employee::class, 'company_id', 'id', 'id', 'user_id');
    }

    public function administrators()
    {
        return $this->belongsToMany(User::class, 'company_user')->withTimestamps();
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function ccaf()
    {
        return $this->belongsTo(Ccaf::class);
    }

    public function mutual()
    {
        return $this->belongsTo(Mutual::class, 'mutual_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function costCenters()
    {
        return $this->hasMany(CostCenter::class);
    }

    public function payrollPeriods()
    {
        return $this->hasMany(PayrollPeriod::class);
    }

    public function periods()
    {
        return $this->payrollPeriods();
    }

    public function economicActivity()
    {
        return $this->belongsTo(EconomicActivity::class);
    }

    public function administrativeDayPolicy()
    {
        return $this->hasOne(CompanyAdministrativeDayPolicy::class);
    }

    public function dayRequests()
    {
        return $this->hasMany(AdministrativeDayRequest::class);
    }

    public function taxableItems()
    {
        return $this->belongsToMany(TaxableItem::class, 'company_taxable_item', 'company_id', 'taxable_item_id')
            ->withPivot('is_enabled')
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::creating(function (self $company) {
            if (! empty($company->rut)) {
                $company->rut = Rut::normalize($company->rut);
            }

            if (! empty($company->representante_rut)) {
                $company->representante_rut = Rut::normalize($company->representante_rut);
            }

            if (empty($company->name) && ! empty($company->razon_social)) {
                $company->name = $company->razon_social;
            }

            if (empty($company->slug)) {
                $company->slug = Str::slug((string) $company->name);
            }

            if (Schema::hasTable('tenants') && Schema::hasColumn('companies', 'tenant_id')) {
                if (! empty($company->tenant_id)) {
                    $alreadyAssigned = static::query()
                        ->withoutGlobalScopes()
                        ->where('tenant_id', $company->tenant_id)
                        ->exists();

                    if ($alreadyAssigned) {
                        throw new \RuntimeException('Un tenant solo puede pertenecer a una única empresa (Regla: 1 RUT, 1 empresa, 1 tenant).');
                    }

                    $tenant = Tenant::query()->find($company->tenant_id);
                    if ($tenant) {
                        if (! empty($company->rut) && ! empty($tenant->rut) && $tenant->rut !== $company->rut) {
                            throw new \RuntimeException('El RUT de la empresa debe coincidir con el RUT de su tenant.');
                        }

                        if (empty($tenant->rut) && ! empty($company->rut)) {
                            $tenant->update(['rut' => $company->rut]);
                        }
                    }
                } else {
                    $existingOrphanTenant = ! empty($company->rut)
                        ? Tenant::query()
                            ->where('rut', $company->rut)
                            ->whereDoesntHave('company')
                            ->first()
                        : null;

                    if ($existingOrphanTenant) {
                        $company->tenant_id = $existingOrphanTenant->id;
                    } else {
                        $tenantName = (string) ($company->razon_social ?: $company->name ?: 'Empresa');
                        $tenant = Tenant::create([
                            'name' => $tenantName,
                            'rut' => $company->rut,
                            'slug' => Tenant::generateUniqueSlug($company->slug ?: $tenantName),
                            'status' => Tenant::STATUS_ACTIVE,
                        ]);

                        $company->tenant_id = $tenant->id;
                    }
                }
            }
        });

        static::updating(function (self $company) {
            if ($company->isDirty('rut')) {
                $company->rut = Rut::normalize($company->rut);
            }

            if ($company->isDirty('representante_rut')) {
                $company->representante_rut = Rut::normalize($company->representante_rut);
            }

            if ($company->isDirty('name') && ! $company->isDirty('slug')) {
                $company->slug = Str::slug((string) $company->name);
            }

            if ($company->isDirty('tenant_id') && ! empty($company->tenant_id)) {
                $alreadyAssigned = static::query()
                    ->withoutGlobalScopes()
                    ->where('tenant_id', $company->tenant_id)
                    ->whereKeyNot($company->id)
                    ->exists();

                if ($alreadyAssigned) {
                    throw new \RuntimeException('Un tenant solo puede pertenecer a una única empresa (Regla: 1 RUT, 1 empresa, 1 tenant).');
                }
            }
        });

        static::updated(function (self $company) {
            if (
                ! empty($company->tenant_id)
                && ($company->wasChanged('name') || $company->wasChanged('razon_social') || $company->wasChanged('rut'))
                && Schema::hasTable('tenants')
            ) {
                $tenant = Tenant::query()->find($company->tenant_id);
                if ($tenant) {
                    $tenant->update([
                        'name' => (string) ($company->razon_social ?: $company->name ?: $tenant->name),
                        'rut' => $company->rut ?? $tenant->rut,
                    ]);
                }
            }
        });
    }
}
