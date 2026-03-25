<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Employees\Models\Employee;

class EmployeeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'item_id',
        'amount',
        'unit',
        'periodicity',
        'total_installments',
        'current_installment',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'is_active'           => 'boolean',
        'total_installments'  => 'integer',
        'current_installment' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Resolve the amount to Chilean pesos (CLP integer).
     *
     * @param  object  $uf   LegalParameter with ->value for UF
     * @param  object  $utm  LegalParameter with ->value for UTM
     * @param  int     $base Base imponible del empleado (required for PERCENTAGE unit)
     */
    public function resolvedAmountCLP(object $uf, object $utm, int $base = 0): int
    {
        $amount = (float) $this->amount;

        return match ($this->unit) {
            'UF'         => (int) round($amount * (float) $uf->value),
            'UTM'        => (int) round($amount * (float) $utm->value),
            'PERCENTAGE' => (int) round($base * $amount / 100),
            default      => (int) round($amount), // CLP
        };
    }

    /**
     * Whether this credit has been fully paid.
     */
    public function isFullyPaid(): bool
    {
        if ($this->item?->type !== Item::TYPE_CREDITO) {
            return false;
        }

        return $this->total_installments !== null
            && $this->current_installment >= $this->total_installments;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFixed($query)
    {
        return $query->where('periodicity', 'fixed');
    }
}
