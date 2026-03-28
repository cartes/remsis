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
        'calculation_type',
        'assignment_type',
        'currency',
        'default_amount',
        'is_taxable',
        'is_gratification_base',
        'is_overtime_base',
    ];

    protected $casts = [
        'is_taxable'            => 'boolean',
        'is_gratification_base' => 'boolean',
        'is_overtime_base'      => 'boolean',
        'default_amount'        => 'decimal:2',
    ];

    // ── Tipos de concepto ─────────────────────────────────────
    const TYPE_HABER_IMPONIBLE    = 'haber_imponible';
    const TYPE_HABER_NO_IMPONIBLE = 'haber_no_imponible';
    const TYPE_DESCUENTO_LEGAL    = 'descuento_legal';
    const TYPE_DESCUENTO_VARIOS   = 'descuento_varios';
    const TYPE_CREDITO            = 'credito';

    // ── Formas de cálculo ─────────────────────────────────────
    const CALC_FIJO                 = 'fijo';
    const CALC_PROPORCIONAL_AUSENCIA = 'proporcional_ausencia';
    const CALC_LIQUIDO              = 'liquido';

    // ── Tipo de asignación ────────────────────────────────────
    const ASSIGN_IGUAL_PARA_TODOS   = 'igual_para_todos';
    const ASSIGN_DISTINTO_POR_PERSONA = 'distinto_por_persona';

    // ── Etiquetas legibles ────────────────────────────────────
    public static array $typeLabels = [
        self::TYPE_HABER_IMPONIBLE    => 'Haber Imponible',
        self::TYPE_HABER_NO_IMPONIBLE => 'Haber No Imponible',
        self::TYPE_DESCUENTO_LEGAL    => 'Descuento Legal',
        self::TYPE_DESCUENTO_VARIOS   => 'Descuento Varios',
        self::TYPE_CREDITO            => 'Crédito',
    ];

    public static array $calcLabels = [
        self::CALC_FIJO                  => 'Fijo',
        self::CALC_PROPORCIONAL_AUSENCIA => 'Proporcional a días',
        self::CALC_LIQUIDO               => 'Líquido',
    ];

    public static array $assignLabels = [
        self::ASSIGN_IGUAL_PARA_TODOS    => 'Igual para todos',
        self::ASSIGN_DISTINTO_POR_PERSONA => 'Por persona',
    ];

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
        return self::$typeLabels[$this->type] ?? $this->type;
    }

    public function getCalcLabelAttribute(): string
    {
        return self::$calcLabels[$this->calculation_type] ?? $this->calculation_type;
    }

    public function getAssignLabelAttribute(): string
    {
        return self::$assignLabels[$this->assignment_type] ?? $this->assignment_type;
    }
}
