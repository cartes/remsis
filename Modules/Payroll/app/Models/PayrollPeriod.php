<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Companies\Models\Company;

class PayrollPeriod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'year',
        'month',
        'start_date',
        'end_date',
        'payment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'year' => 'integer',
        'month' => 'integer',
    ];

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_PAID = 'paid';

    /**
     * Month names in Spanish
     */
    const MONTH_NAMES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /**
     * Relationships
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Scopes
     */
    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('year', $now->year)
                    ->where('month', $now->month);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Business Logic Methods
     */
    public function canModify(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_OPEN]);
    }

    public function canClose(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function canReopen(): bool
    {
        // Only super-admin can reopen closed periods
        return auth()->user()?->hasRole('super-admin') && 
               in_array($this->status, [self::STATUS_CLOSED, self::STATUS_PAID]);
    }

    public function getDisplayName(): string
    {
        $monthName = self::MONTH_NAMES[$this->month] ?? 'Mes ' . $this->month;
        return $monthName . ' ' . $this->year;
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-700 border-gray-200',
            self::STATUS_OPEN => 'bg-blue-100 text-blue-700 border-blue-200',
            self::STATUS_CLOSED => 'bg-yellow-100 text-yellow-700 border-yellow-200',
            self::STATUS_PAID => 'bg-green-100 text-green-700 border-green-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_OPEN => 'Abierto',
            self::STATUS_CLOSED => 'Cerrado',
            self::STATUS_PAID => 'Pagado',
            default => ucfirst($this->status),
        };
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($period) {
            // Auto-generate name if not provided
            if (empty($period->name)) {
                $period->name = $period->getDisplayName();
            }
            
            // Set default status if not provided
            if (empty($period->status)) {
                $period->status = self::STATUS_DRAFT;
            }
        });
    }
}
