<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employees\Models\Employee;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\Isapre;
use Modules\AdminPanel\Models\Ccaf;

class PayrollLine extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payrolls';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'employee_id',
        'company_id',
        'payroll_period_id',
        'period_year',
        'period_month',
        'worked_days',
        'overtime_hours',
        'overtime_amount',
        'base_salary',      // haber_base: sueldo del contrato
        'gratification_amount', // GRATIFICACION
        'gross_salary',     // imponible: haber_base + extras + grati
        'afp_id',
        'afp_amount',       // imponible * 0.10
        'isapre_id',
        'isapre_amount',    // imponible * 0.07
        'ccaf_id',
        'ccaf_amount',
        'cesantia_amount',  // imponible * 0.006
        'impuesto_unico_amount',
        'anticipos_amount',
        'otros_descuentos',
        'total_deductions', // afp + salud + cesantia
        'net_salary',       // total_neto: imponible - deducciones
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
    
    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function afp()
    {
        return $this->belongsTo(Afp::class);
    }

    public function isapre()
    {
        return $this->belongsTo(Isapre::class);
    }
}
