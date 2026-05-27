<?php

declare(strict_types=1);

namespace Modules\Employees\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;

class AdministrativeDayRequest extends Model
{
    use BelongsToTenant;

    protected $table = 'administrative_day_requests';

    protected $fillable = [
        'company_id',
        'employee_id',
        'requested_date',
        'status',
        'notes',
        'resolved_by',
        'resolved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'requested_date' => 'date:Y-m-d',
        'resolved_at'    => 'datetime',
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

    public function usage(): HasOne
    {
        return $this->hasOne(AdministrativeDayUsage::class, 'request_id');
    }
}
