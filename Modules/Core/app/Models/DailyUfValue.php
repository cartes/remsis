<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyUfValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_ipc_value_id',
        'date',
        'cycle_start_date',
        'cycle_end_date',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'cycle_start_date' => 'date',
            'cycle_end_date' => 'date',
            'value' => 'decimal:2',
        ];
    }

    public function monthlyIpcValue(): BelongsTo
    {
        return $this->belongsTo(MonthlyIpcValue::class);
    }
}
