<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employees\Models\Employee;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User; 
use Modules\AdminPanel\Models\Bank;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\Isapre;
use Modules\AdminPanel\Models\Ccaf;
// use Modules\Payroll\Database\Factories\PayrollFactory;

class Payroll extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'employee_id',
        'company_id',
        'bank_id',
        'bank_account_number',
        'bank_account_type',
        'payroll_period_id',
        'period_year',
        'period_month',
        'worked_days',
        'overtime_hours',
        'base_salary',
        'gross_salary',
        'afp_id',
        'afp_amount',
        'isapre_id',
        'isapre_amount',
        'ccaf_id',
        'ccaf_amount',
        'cesantia_amount',
        'impuesto_unico_amount',
        'anticipos_amount',
        'otros_descuentos',
        'total_deductions',
        'net_salary',
        'payment_date',
        'status',
        'processed_at',
        'processed_by',
        'notes'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
    public function afp()
    {
        return $this->belongsTo(Afp::class);
    }
    public function isapre()
    {
        return $this->belongsTo(Isapre::class);
    }
    public function ccaf()
    {
        return $this->belongsTo(Ccaf::class);
    }
    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }
    public function details()
    {
        return $this->hasMany(PayrollDetail::class);
    }
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }


}
