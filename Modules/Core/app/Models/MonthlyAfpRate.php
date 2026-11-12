<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tasa previsional de AFP para un período mensual específico (10% obligatorio + comisión AFP).
 */
class MonthlyAfpRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_indicator_id',
        'afp_code',
        'afp_name',
        'mandatory_rate',
        'commission_rate',
        'total_rate',
        'independent_rate',
    ];

    protected $casts = [
        'mandatory_rate' => 'float',
        'commission_rate' => 'float',
        'total_rate' => 'float',
        'independent_rate' => 'float',
    ];

    public function monthlyIndicator(): BelongsTo
    {
        return $this->belongsTo(MonthlyIndicator::class, 'monthly_indicator_id');
    }
}

