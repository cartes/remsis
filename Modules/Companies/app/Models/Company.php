<?php

namespace Modules\Companies\Models;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
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
        return $this->belongsToMany(User::class, 'company_user');
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
        static::creating(function ($company) {
            if (empty($company->tenant_id) && app()->bound(TenantContext::class)) {
                $tenantId = app(TenantContext::class)->tenantId();
                if ($tenantId !== null) {
                    $company->tenant_id = $tenantId;
                }
            }

            if (empty($company->name) && ! empty($company->razon_social)) {
                $company->name = $company->razon_social;
            }

            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });

        static::updating(function ($company) {
            if ($company->isDirty('name') && ! $company->isDirty('slug')) {
                $company->slug = Str::slug($company->name);
            }
        });
    }
}
