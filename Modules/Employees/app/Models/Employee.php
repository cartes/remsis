<?php

namespace Modules\Employees\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\Bank;
use Modules\AdminPanel\Models\Ccaf;
use Modules\AdminPanel\Models\Isapre;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;

// use Modules\Employees\Database\Factories\EmployeeFactory;

class Employee extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'employees';

    /** Unidades en que se pacta el plan de Isapre. */
    public const HEALTH_UNITS = ['UF' => 'UF', 'CLP' => 'Pesos ($)', 'PERCENTAGE' => '% de la renta imponible'];

    /** Regímenes tributarios del APV (Art. 20 L DL 3.500). */
    public const APV_REGIMES = [
        'A' => 'Régimen A (bonificación fiscal, no rebaja impuesto)',
        'B' => 'Régimen B (rebaja la base del impuesto único)',
    ];

    /** Tramos de asignación familiar. */
    public const FAMILY_ALLOWANCE_BRACKETS = ['A', 'B', 'C', 'D'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
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
        'birth_date',
        'nationality',
        'marital_status',
        'num_dependents',
        'hire_date',
        'work_schedule',
        'work_schedule_type',
        'part_time_hours',
        'part_time_schedule',
        'cost_center_id',
        'ccaf_id',
        'isapre_id',
        'afp_id',
        'health_contribution',
        'health_contribution_unit',
        'apv_amount',
        'apv_regime',
        'family_allowance_bracket',
        'salary',
        'salary_type',
        'contract_type',
        'status',
        'is_in_payroll',
        'emergency_contact_name',
        'emergency_contact_phone',
        'gender',
        'health_system',
        'payment_method',
        'vacation_initial_balance',
        'vacation_initial_balance_date',
        'previous_employment_months',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'birth_date' => 'date',
        'part_time_schedule' => 'array',
        'is_in_payroll' => 'boolean',
        'vacation_initial_balance' => 'float',
        'vacation_initial_balance_date' => 'date',
        'previous_employment_months' => 'integer',
    ];

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d');
    }

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

    public function costCenter()
    {
        return $this->belongsTo(\Modules\Companies\Models\CostCenter::class);
    }

    /**
     * Relación con ítems asignados al colaborador.
     * Usa FQCN string para evitar dependencia circular de namespace con el módulo Payroll.
     */
    public function employeeItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('Modules\Payroll\Models\EmployeeItem', 'employee_id');
    }

    /**
     * Historial de liquidaciones de sueldo procesadas para este colaborador.
     * Usa FQCN string para evitar dependencia circular de namespace con el módulo Payroll.
     */
    public function payrolls(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('Modules\Payroll\Models\Payroll', 'employee_id');
    }

    /**
     * Carpeta digital: contratos, anexos y comprobantes del colaborador.
     */
    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('Modules\Employees\Models\EmployeeDocument', 'employee_id');
    }

    /**
     * Bitácora de auditoría: todos los eventos registrados para este colaborador.
     */
    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeLog::class, 'employee_id');
    }

    /**
     * Registros de asistencia del colaborador.
     */
    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'employee_id');
    }

    /**
     * Historial de contratos laborales del colaborador.
     */
    public function contracts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmploymentContract::class, 'employee_id');
    }

    /**
     * Contrato laboral vigente del colaborador.
     */
    public function activeContract(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        $today = now()->toDateString();

        // Con historial de anexos puede haber varias versiones activas: rige la que cubre el día de hoy.
        return $this->hasOne(EmploymentContract::class, 'employee_id')
            ->ofMany(['valid_from' => 'max', 'id' => 'max'], fn ($query) => $query
                ->where('status', EmploymentContract::STATUS_ACTIVE)
                ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today))
                ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today)));
    }

    /**
     * Solicitudes y registros de vacaciones del colaborador.
     */
    public function vacationRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VacationRequest::class, 'employee_id');
    }

    /**
     * Sincroniza o crea el contrato activo del colaborador con sus datos laborales actuales.
     */
    public function syncActiveContract(): ?EmploymentContract
    {
        if (empty($this->company_id) || ! \Illuminate\Support\Facades\Schema::hasTable('employment_contracts')) {
            return null;
        }

        $company = $this->relationLoaded('company')
            ? $this->company
            : Company::query()->withoutGlobalScopes()->find($this->company_id);

        $weeklyHours = (int) ($this->part_time_hours ?: ($company?->weekly_hours ?: 42));
        if ($weeklyHours <= 0) {
            $weeklyHours = 42;
        }

        // La ficha refleja las condiciones vigentes hoy: se sincroniza esa versión y no las de
        // anexos futuros ni las ya cerradas. Sin ninguna vigente, la más reciente.
        $versions = fn () => EmploymentContract::query()
            ->withoutGlobalScopes()
            ->where('employee_id', $this->id)
            ->where('company_id', $this->company_id);
        $contract = $versions()->inForceOn(now())->orderByDesc('valid_from')->orderByDesc('id')->first()
            ?? $versions()->orderByDesc('valid_from')->orderByDesc('id')->first();

        $attributes = [
            'tenant_id' => $this->tenant_id ?? $company?->tenant_id,
            'company_id' => $this->company_id,
            'employee_id' => $this->id,
            'cost_center_id' => $this->cost_center_id,
            'contract_number' => $contract?->contract_number ?: sprintf('CTR-%d-%04d', $this->company_id, $this->id),
            'contract_type' => $this->contract_type ?: EmploymentContract::TYPE_INDEFINITE,
            'position' => $this->position,
            'start_date' => $this->hire_date ? $this->hire_date->format('Y-m-d') : ($contract?->start_date?->format('Y-m-d') ?: now()->toDateString()),
            'base_salary' => round((float) ($this->salary ?? 0), 0),
            'salary_type' => $this->salary_type ?: 'mensual',
            'gratification_type' => $company?->gratification_system ?: 'art_50',
            'weekly_hours' => $weeklyHours,
            'work_schedule_type' => $this->work_schedule_type ?: EmploymentContract::SCHEDULE_FULL_TIME,
            'status' => ($this->status ?? 'active') === 'active'
                ? EmploymentContract::STATUS_ACTIVE
                : EmploymentContract::STATUS_TERMINATED,
        ];

        if ($contract) {
            // El contrato original rige desde el ingreso; un anexo conserva su propia fecha de vigencia.
            if ($contract->version_type !== EmploymentContract::VERSION_AMENDMENT) {
                $attributes['valid_from'] = $attributes['start_date'];
            }
            $contract->fill($attributes)->save();

            return $contract;
        }

        return EmploymentContract::query()->withoutGlobalScopes()->create($attributes + [
            'valid_from' => $attributes['start_date'],
            'version_type' => EmploymentContract::VERSION_CONTRACT,
        ]);
    }

    protected $appends = ['full_name', 'completion_percentage'];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getCompletionPercentageAttribute()
    {
        $fields = [
            'first_name', 'last_name', 'rut', 'birth_date', 'email', 'phone', 'nationality', 'marital_status', 'address',
            'position', 'hire_date', 'contract_type', 'work_schedule', 'cost_center_id',
            'afp_id', 'isapre_id', 'ccaf_id', 'health_contribution',
            'salary', 'salary_type',
            'bank_id', 'bank_account_number', 'bank_account_type',
            'emergency_contact_name', 'emergency_contact_phone',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (! empty($this->{$field})) {
                $filled++;
            }
        }

        return round(($filled / count($fields)) * 100);
    }
}
