<?php

namespace Modules\Employees\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Companies\Models\Company;

class EmployeeAdministrativeDayBalance extends Model
{
    use BelongsToTenant;

    protected $table = 'employee_administrative_day_balances';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'employee_id',
        'policy_id',
        'cycle_key',
        'cycle_starts_on',
        'cycle_ends_on',
        'policy_snapshot_days',
        'policy_snapshot_unit',
        'policy_snapshot_cycle',
        'assigned_units',
        'used_units',
        'available_units',
    ];

    protected $casts = [
        'cycle_starts_on' => 'date:Y-m-d',
        'cycle_ends_on' => 'date:Y-m-d',
        'policy_snapshot_days' => 'decimal:2',
        'assigned_units' => 'decimal:2',
        'used_units' => 'decimal:2',
        'available_units' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(AdministrativeDayUsage::class, 'balance_id');
    }
}
