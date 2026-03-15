<?php

namespace Modules\Payroll\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Companies\Models\Company;

class FreelancerReceipt extends Model
{
    use BelongsToTenant;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'freelancer_id',
        'company_id',
        'receipt_number',
        'issue_date',
        'gross_amount',
        'retention_amount',
        'net_amount',
        'issuer',
        'status',
        'document_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'gross_amount' => 'decimal:2',
        'retention_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function freelancer()
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
