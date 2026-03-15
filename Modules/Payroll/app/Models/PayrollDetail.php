<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'concept',
        'amount',
        'type',
        'description',
    ];

    // Relación inversa con Payroll
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
