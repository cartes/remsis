<?php

namespace Modules\Payroll\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Companies\Models\Company;
use Modules\AdminPanel\Models\Bank;

class Freelancer extends Model
{
    use BelongsToTenant;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'rut',
        'email',
        'phone',
        'address',
        'profession',
        'bank_id',
        'bank_account_number',
        'bank_account_type',
        'default_gross_fee',
        'default_retention_rate',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_gross_fee' => 'decimal:2',
        'default_retention_rate' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
    
    public function receipts()
    {
        return $this->hasMany(FreelancerReceipt::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
