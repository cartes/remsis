<?php

declare(strict_types=1);

namespace Modules\Employees\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CostCenter;

/**
 * Contrato de trabajo vigente o histórico de un colaborador (Código del Trabajo Art. 10).
 * Centraliza tipo de contrato (indefinido, plazo fijo, por obra), sueldo base, gratificación
 * y jornada ordinaria semanal (Ley 21.561 de 40 Horas).
 */
class EmploymentContract extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    public const TYPE_INDEFINITE = 'indefinido';

    public const TYPE_FIXED_TERM = 'plazo_fijo';

    public const TYPE_PROJECT = 'por_obra';

    public const TYPE_FREELANCE = 'honorarios';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_TERMINATED = 'terminated';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_DRAFT = 'draft';

    public const SCHEDULE_FULL_TIME = 'full_time';

    public const SCHEDULE_PART_TIME = 'part_time';

    public const SCHEDULE_ART_22 = 'art_22';

    protected $table = 'employment_contracts';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'employee_id',
        'cost_center_id',
        'contract_number',
        'contract_type',
        'position',
        'start_date',
        'end_date',
        'base_salary',
        'salary_type',
        'gratification_type',
        'weekly_hours',
        'work_schedule_type',
        'distribution_days',
        'is_afc_exempt',
        'status',
        'termination_date',
        'termination_cause_article',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'termination_date' => 'date',
        'base_salary' => 'float',
        'weekly_hours' => 'integer',
        'distribution_days' => 'integer',
        'is_afc_exempt' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function isIndefinite(): bool
    {
        return $this->contract_type === self::TYPE_INDEFINITE;
    }

    public function isFixedTerm(): bool
    {
        return in_array($this->contract_type, [self::TYPE_FIXED_TERM, self::TYPE_PROJECT], true);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Factor legal de hora extraordinaria con recargo del 50% (Art. 32 CT)
     * calculado dinámicamente según la jornada semanal pactada en el contrato:
     * Factor = ((1 / 30) * 7 / weekly_hours) * 1.5
     * Mantiene 6 decimales para cálculos intermedios según reglas de Remsis.
     */
    public function overtimeHourlyFactor(float $surchargeMultiplier = 1.5): float
    {
        if ($this->work_schedule_type === self::SCHEDULE_ART_22 || $this->weekly_hours <= 0) {
            return 0.0;
        }

        $factor = ((1 / 30) * 7 / $this->weekly_hours) * $surchargeMultiplier;

        return round($factor, 6);
    }

    /**
     * Calcula el valor de 1 hora extraordinaria en CLP para este contrato.
     */
    public function calculateOvertimeHourValueClp(float $additionalOvertimeBaseClp = 0.0): float
    {
        $base = $this->base_salary + $additionalOvertimeBaseClp;

        return round($base * $this->overtimeHourlyFactor(), 2);
    }
}

