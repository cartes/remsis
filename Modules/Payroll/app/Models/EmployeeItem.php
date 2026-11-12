<?php

namespace Modules\Payroll\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Companies\Models\Company;
use Modules\Core\Models\Tenant;
use Modules\Employees\Models\Employee;

class EmployeeItem extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'company_id',
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
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'total_installments' => 'integer',
        'current_installment' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Resolve the amount to Chilean pesos (CLP integer).
     *
     * @param  object  $uf  LegalParameter with ->value for UF
     * @param  object  $utm  LegalParameter with ->value for UTM
     * @param  int  $base  Base imponible del empleado (required for PERCENTAGE unit)
     * @param  int  $workedDays  Cantidad de días trabajados en el mes
     */
    public function resolvedAmountCLP(object $uf, object $utm, int $base = 0, int $workedDays = 30): int
    {
        $amount = (float) $this->amount;

        $resolved = match ($this->unit) {
            'UF' => (int) round($amount * (float) $uf->value),
            'UTM' => (int) round($amount * (float) $utm->value),
            'PERCENTAGE' => (int) round($base * $amount / 100),
            default => (int) round($amount), // CLP
        };

        if ($this->item && $this->item->calculation_type === Item::CALC_PROPORCIONAL_AUSENCIA) {
            $resolved = (int) round(($resolved / 30) * $workedDays);
        }

        return $resolved;
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
