<?php

declare(strict_types=1);

namespace Modules\Employees\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;

/**
 * Licencia médica de un colaborador. Los días son corridos: incluyen sábados, domingos y festivos.
 *
 * Los días "efectivos" son los que la resolución reconoce: los prescritos mientras está en trámite
 * o autorizada, los autorizados si fue reducida y ninguno si fue rechazada o anulada.
 */
class MedicalLeave extends Model
{
    use BelongsToTenant;

    public const TYPE_COMMON = 'enfermedad_comun';

    public const TYPE_PREVENTIVE = 'medicina_preventiva';

    public const TYPE_MATERNITY = 'maternal';

    public const TYPE_SICK_CHILD = 'hijo_menor';

    public const TYPE_WORK_ACCIDENT = 'accidente_trabajo';

    public const TYPE_OCCUPATIONAL_DISEASE = 'enfermedad_profesional';

    public const TYPE_PREGNANCY_PATHOLOGY = 'patologia_embarazo';

    /** Tipos del formulario de licencia médica, en su orden oficial (1 a 7). */
    public const TYPE_LABELS = [
        self::TYPE_COMMON => 'Enfermedad o accidente común',
        self::TYPE_PREVENTIVE => 'Prórroga de medicina preventiva',
        self::TYPE_MATERNITY => 'Maternal (pre y postnatal)',
        self::TYPE_SICK_CHILD => 'Enfermedad grave de hijo menor de un año',
        self::TYPE_WORK_ACCIDENT => 'Accidente del trabajo o de trayecto',
        self::TYPE_OCCUPATIONAL_DISEASE => 'Enfermedad profesional',
        self::TYPE_PREGNANCY_PATHOLOGY => 'Patología del embarazo',
    ];

    /** Cobertura de la Ley 16.744: paga la mutualidad o el ISL, no el sistema de salud común. */
    public const WORK_RELATED_TYPES = [self::TYPE_WORK_ACCIDENT, self::TYPE_OCCUPATIONAL_DISEASE];

    public const STATUS_IN_PROCESS = 'in_process';

    public const STATUS_AUTHORIZED = 'authorized';

    public const STATUS_REDUCED = 'reduced';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_IN_PROCESS => 'En trámite',
        self::STATUS_AUTHORIZED => 'Autorizada',
        self::STATUS_REDUCED => 'Reducida',
        self::STATUS_REJECTED => 'Rechazada',
        self::STATUS_CANCELLED => 'Anulada',
    ];

    /** Resoluciones que puede informar la entidad (COMPIN, Isapre o mutualidad). */
    public const RESOLUTION_STATUSES = [self::STATUS_AUTHORIZED, self::STATUS_REDUCED, self::STATUS_REJECTED];

    public const PAYER_LABELS = [
        'compin' => 'COMPIN / Fonasa',
        'ccaf' => 'Caja de compensación',
        'isapre' => 'Isapre',
        'mutual' => 'Mutualidad',
        'isl' => 'Instituto de Seguridad Laboral',
    ];

    protected $fillable = [
        'tenant_id',
        'company_id',
        'employee_id',
        'continues_leave_id',
        'folio',
        'type',
        'start_date',
        'end_date',
        'days',
        'status',
        'authorized_days',
        'resolution_date',
        'resolution_notes',
        'payer_type',
        'payer_name',
        'subsidy_base_amount',
        'subsidy_daily_amount',
        'subsidy_days',
        'subsidy_amount',
        'subsidy_detail',
        'notes',
        'registered_by',
        'resolved_by',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'resolution_date' => 'date:Y-m-d',
        'days' => 'integer',
        'authorized_days' => 'integer',
        'subsidy_days' => 'integer',
        'subsidy_base_amount' => 'float',
        'subsidy_daily_amount' => 'float',
        'subsidy_amount' => 'float',
        'subsidy_detail' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function previous(): BelongsTo
    {
        return $this->belongsTo(self::class, 'continues_leave_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? (string) $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? (string) $this->status;
    }

    public function getPayerLabelAttribute(): string
    {
        return $this->payer_name ?: (self::PAYER_LABELS[$this->payer_type] ?? (string) $this->payer_type);
    }

    /** Días reconocidos por la resolución vigente. */
    public function getEffectiveDaysAttribute(): int
    {
        return match ($this->status) {
            self::STATUS_REJECTED, self::STATUS_CANCELLED => 0,
            self::STATUS_REDUCED => min((int) $this->authorized_days, (int) $this->days),
            default => (int) $this->days,
        };
    }

    /** Último día reconocido, o null si la licencia no reconoce ninguno. */
    public function effectiveEndDate(): ?CarbonImmutable
    {
        $days = $this->effective_days;

        return $days > 0
            ? CarbonImmutable::parse($this->start_date->toDateString())->addDays($days - 1)
            : null;
    }

    /** Días de carencia que absorbió esta licencia (Art. 14 DFL 44). */
    public function getWaitingDaysAttribute(): int
    {
        return max(0, $this->effective_days - (int) $this->subsidy_days);
    }
}
