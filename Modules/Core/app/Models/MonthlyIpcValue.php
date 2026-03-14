<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyIpcValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'reference_period',
        'published_at',
        'value',
        'source_series',
    ];

    protected function casts(): array
    {
        return [
            'reference_period' => 'date',
            'published_at' => 'date',
            'value' => 'decimal:4',
        ];
    }

    public function dailyUfValues(): HasMany
    {
        return $this->hasMany(DailyUfValue::class);
    }
}
