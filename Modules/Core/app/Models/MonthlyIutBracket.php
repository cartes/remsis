<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tramo de la tabla de Impuesto Único de Segunda Categoría (IUSC / IUT) del SII.
 */
class MonthlyIutBracket extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_indicator_id',
        'bracket_number',
        'from_utm',
        'to_utm',
        'factor',
        'deduction_utm',
        'max_effective_rate',
    ];

    protected $casts = [
        'bracket_number' => 'integer',
        'from_utm' => 'float',
        'to_utm' => 'float',
        'factor' => 'float',
        'deduction_utm' => 'float',
        'max_effective_rate' => 'float',
    ];

    public function monthlyIndicator(): BelongsTo
    {
        return $this->belongsTo(MonthlyIndicator::class, 'monthly_indicator_id');
    }
}

