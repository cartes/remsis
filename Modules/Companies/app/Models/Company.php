<?php

namespace Modules\Companies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Users\Models\User;
use Modules\AdminPanel\Models\Ccaf;
use Modules\Employees\Models\Employee;

// use Modules\Companies\Database\Factories\CompanyFactory;

class Company extends Model
{
    use HasFactory;

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
        'ccaf_id',
        'cuenta_bancaria',
        'representante_nombre',
        'representante_rut',
        'representante_cargo',
        'representante_email'
    ];


    public function users()
    {
        return $this->hasManyThrough(User::class, Employee::class, 'company_id', 'id', 'id', 'user_id');
    }

    public function ccaf()
    {
        return $this->belongsTo(Ccaf::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
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
