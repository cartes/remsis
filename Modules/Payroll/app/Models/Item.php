<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Companies\Models\Company;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'is_taxable',
        'is_gratification_base',
    ];

    protected $casts = [
        'is_taxable'            => 'boolean',
        'is_gratification_base' => 'boolean',
    ];

    // Types
    const TYPE_HABER_IMPONIBLE    = 'haber_imponible';
    const TYPE_HABER_NO_IMPONIBLE = 'haber_no_imponible';
    const TYPE_DESCUENTO_LEGAL    = 'descuento_legal';
    const TYPE_DESCUENTO_VARIOS   = 'descuento_varios';
    const TYPE_CREDITO            = 'credito';

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeItems()
    {
        return $this->hasMany(EmployeeItem::class);
    }

    // Scopes
    public function scopeHaberesImponibles($query)
    {
        return $query->where('type', self::TYPE_HABER_IMPONIBLE);
    }

    public function scopeHaberesNoImponibles($query)
    {
        return $query->where('type', self::TYPE_HABER_NO_IMPONIBLE);
    }

    public function scopeDescuentos($query)
    {
        return $query->whereIn('type', [self::TYPE_DESCUENTO_LEGAL, self::TYPE_DESCUENTO_VARIOS]);
    }

    public function scopeCreditos($query)
    {
        return $query->where('type', self::TYPE_CREDITO);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_HABER_IMPONIBLE    => 'Haber Imponible',
            self::TYPE_HABER_NO_IMPONIBLE => 'Haber No Imponible',
            self::TYPE_DESCUENTO_LEGAL    => 'Descuento Legal',
            self::TYPE_DESCUENTO_VARIOS   => 'Descuento Varios',
            self::TYPE_CREDITO            => 'Crédito',
            default                       => $this->type,
        };
    }
}
