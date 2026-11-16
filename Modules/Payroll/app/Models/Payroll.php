<?php

namespace Modules\Payroll\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\Bank;
use Modules\AdminPanel\Models\Ccaf;
use Modules\AdminPanel\Models\Isapre;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Users\Models\User;

// use Modules\Payroll\Database\Factories\PayrollFactory;

class Payroll extends Model
{
    use BelongsToTenant;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'employee_id',
        'company_id',
        'bank_id',
        'bank_name',
        'bank_account_number',
        'bank_account_type',
        'payroll_period_id',
        'period_year',
        'period_month',
        'worked_days',
        'medical_leave_days',
        'overtime_hours',
        'overtime_amount',
        'comisiones_amount',
        'semana_corrida_amount',
        'bonos_imponibles_amount',
        'aguinaldos_amount',
        'base_salary',
        'gratification_amount',
        'gross_salary',
        'meal_allowance',
        'mobility_allowance',
        'asignacion_familiar_amount',
        'non_taxable_earnings',
        'total_earnings',
        'afp_id',
        'afp_amount',
        'isapre_id',
        'isapre_amount',
        'ccaf_id',
        'ccaf_amount',
        'cesantia_amount',
        'apv_amount',
        'tributable_base',
        'impuesto_unico_amount',
        'anticipos_amount',
        'otros_descuentos',
        'descuentos_items_amount',
        'creditos_amount',
        'employer_sis_amount',
        'employer_afc_amount',
        'employer_mutual_amount',
        'employer_afp_amount',
        'employer_social_security_amount',
        'descuentos_exceso_amount',
        'total_deductions',
        'net_salary',
        'payment_date',
        'status',
        'processed_at',
        'processed_by',
        'notes',
    ];

    /**
     * Una liquidación queda emitida —y visible para el colaborador— cuando su período está cerrado o pagado.
     * Antes de eso los montos todavía pueden cambiar con un recálculo.
     */
    public const ISSUED_PERIOD_STATUSES = [PayrollPeriod::STATUS_CLOSED, PayrollPeriod::STATUS_PAID];

    public function scopeIssued($query)
    {
        return $query->whereHas('period', fn ($period) => $period
            ->withoutGlobalScopes()
            ->whereIn('status', self::ISSUED_PERIOD_STATUSES));
    }

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

    public function getTypeAttribute(): string
    {
        return 'normal';
    }
}
