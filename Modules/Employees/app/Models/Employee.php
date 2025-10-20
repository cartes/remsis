<?php

namespace Modules\Employees\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;
use Modules\AdminPanel\Models\Ccaf;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\Isapre;
use Modules\AdminPanel\Models\Bank;
// use Modules\Employees\Database\Factories\EmployeeFactory;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'bank_id',
        'bank_account_number',
        'bank_account_type',
        'first_name',
        'last_name',
        'rut',
        'email',
        'phone',
        'address',
        'position',
        'ccaf_id',
        'isapre_id',
        'afp_id',
        'salary',
        'salary_type',
        'contract_type',
        'hire_date',
        'status',
    ];

    protected $casts = [
        'hire_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function ccaf()
    {
        return $this->belongsTo(Ccaf::class);
    }

    public function afp()
    {
        return $this->belongsTo(Afp::class);
    }

    public function isapre()
    {
        return $this->belongsTo(Isapre::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

}
