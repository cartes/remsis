<?php

declare(strict_types=1);

namespace Modules\Employees\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;

/**
 * Solicitud o registro de feriado anual (Art. 67 CT). `working_days` son los días hábiles
 * que se descuentan del saldo; sábados, domingos y festivos del rango no cuentan (Art. 69).
 */
class VacationRequest extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const ORIGIN_EMPLOYEE = 'employee';

    public const ORIGIN_COMPANY = 'company';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_APPROVED => 'Aprobada',
        self::STATUS_REJECTED => 'Rechazada',
        self::STATUS_CANCELLED => 'Anulada',
    ];

    protected $fillable = [
        'tenant_id',
        'company_id',
        'employee_id',
        'start_date',
        'end_date',
        'working_days',
        'status',
        'origin',
        'notes',
        'resolved_by',
        'resolved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'working_days' => 'float',
        'resolved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? (string) $this->status;
    }
}
