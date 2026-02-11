<?php

namespace Modules\Companies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Users\Models\User;
use Modules\AdminPanel\Models\Ccaf;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payroll;

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
        'work_schedule'
    ];

    protected $casts = [
        'work_schedule' => 'array',
        'gratification_months' => 'integer',
    ];


    public function users()
    {
        return $this->hasManyThrough(User::class, Employee::class, 'company_id', 'id', 'id', 'user_id');
    }

    public function bank()
    {
        return $this->belongsTo(\Modules\AdminPanel\Models\Bank::class);
    }

    public function ccaf()
    {
        return $this->belongsTo(Ccaf::class);
    }

    public function mutual()
    {
        return $this->belongsTo(\Modules\AdminPanel\Models\Mutual::class, 'mutual_id');
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
        return $this->hasMany(\Modules\Companies\Models\CostCenter::class);
    }

    protected static function booted()
    {
        static::creating(function ($company) {
            if (empty($company->name) && !empty($company->razon_social)) {
                $company->name = $company->razon_social;
            }
        });
    }

}
