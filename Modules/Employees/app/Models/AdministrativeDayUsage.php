<?php

namespace Modules\Employees\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Companies\Models\Company;

class AdministrativeDayUsage extends Model
{
    use BelongsToTenant;

    protected $table = 'administrative_day_usages';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'employee_id',
        'attendance_record_id',
        'balance_id',
        'request_id',
        'used_units',
        'used_on',
        'notes',
    ];

    protected $casts = [
        'used_on' => 'date:Y-m-d',
        'used_units' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function balance(): BelongsTo
    {
        return $this->belongsTo(EmployeeAdministrativeDayBalance::class, 'balance_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDayRequest::class, 'request_id');
    }
}
