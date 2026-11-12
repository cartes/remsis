<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tramo de Asignación Familiar (A, B, C, D) fijado por ley para el período mensual.
 */
class MonthlyFamilyAllowanceBracket extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_indicator_id',
        'bracket_letter',
        'income_from',
        'income_to',
        'amount_per_dependent',
    ];

    protected $casts = [
        'income_from' => 'float',
        'income_to' => 'float',
        'amount_per_dependent' => 'float',
    ];

    public function monthlyIndicator(): BelongsTo
    {
        return $this->belongsTo(MonthlyIndicator::class, 'monthly_indicator_id');
    }
}

