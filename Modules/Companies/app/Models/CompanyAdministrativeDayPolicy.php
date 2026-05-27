<?php

namespace Modules\Companies\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAdministrativeDayPolicy extends Model
{
    use BelongsToTenant;
    protected $table = 'company_administrative_day_policies';

    protected $fillable = [
        'company_id',
        'is_enabled',
        'days_per_cycle',
        'unit',
        'cycle_type',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'days_per_cycle' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
