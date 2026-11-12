<?php

declare(strict_types=1);

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Modules\Companies\Models\Company;
use Modules\Core\Models\LreConcept;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'items';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'lre_concept_id',
        'name',
        'code',
        'type',
        'calculation_type',
        'assignment_type',
        'currency',
        'default_amount',
        'is_taxable',
        'is_tributable',
        'is_taxable_afc',
        'is_gratification_base',
        'is_overtime_base',
        'is_severance_base',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_tributable' => 'boolean',
        'is_taxable_afc' => 'boolean',
        'is_gratification_base' => 'boolean',
        'is_overtime_base' => 'boolean',
        'is_severance_base' => 'boolean',
        'default_amount' => 'decimal:2',
    ];

    // ── Tipos de concepto ─────────────────────────────────────
    const TYPE_HABER_IMPONIBLE = 'haber_imponible';

    const TYPE_HABER_NO_IMPONIBLE = 'haber_no_imponible';

    const TYPE_DESCUENTO_LEGAL = 'descuento_legal';

    const TYPE_DESCUENTO_VARIOS = 'descuento_varios';

    const TYPE_CREDITO = 'credito';

    // ── Formas de cálculo ─────────────────────────────────────
    const CALC_FIJO = 'fijo';

    const CALC_PROPORCIONAL_AUSENCIA = 'proporcional_ausencia';

    const CALC_LIQUIDO = 'liquido';

    // ── Tipo de asignación ────────────────────────────────────
    const ASSIGN_IGUAL_PARA_TODOS = 'igual_para_todos';

    const ASSIGN_DISTINTO_POR_PERSONA = 'distinto_por_persona';

    // ── Etiquetas legibles ────────────────────────────────────
    public static array $typeLabels = [
        self::TYPE_HABER_IMPONIBLE => 'Haber Imponible',
        self::TYPE_HABER_NO_IMPONIBLE => 'Haber No Imponible',
        self::TYPE_DESCUENTO_LEGAL => 'Descuento Legal',
        self::TYPE_DESCUENTO_VARIOS => 'Descuento Varios',
        self::TYPE_CREDITO => 'Crédito',
    ];

    public static array $calcLabels = [
        self::CALC_FIJO => 'Fijo',
        self::CALC_PROPORCIONAL_AUSENCIA => 'Proporcional a días',
        self::CALC_LIQUIDO => 'Líquido',
    ];

    public static array $assignLabels = [
        self::ASSIGN_IGUAL_PARA_TODOS => 'Igual para todos',
        self::ASSIGN_DISTINTO_POR_PERSONA => 'Por persona',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if (empty($item->tenant_id) && ! empty($item->company_id) && Schema::hasColumn('items', 'tenant_id')) {
                $item->tenant_id = Company::query()
                    ->withoutGlobalScopes()
                    ->whereKey($item->company_id)
                    ->value('tenant_id');
            }

            // Si tiene asociado un concepto LRE DT, heredamos sus banderas normativas (Art. 41, 42 y 58 CT)
            if (! empty($item->lre_concept_id) && Schema::hasTable('lre_concepts_dictionary')) {
                $lre = LreConcept::query()->find($item->lre_concept_id);
                if ($lre) {
                    $item->is_taxable = (bool) $lre->is_taxable;
                    if (Schema::hasColumn('items', 'is_tributable')) {
                        $item->is_tributable = (bool) $lre->is_tributable;
                    }
                    if (Schema::hasColumn('items', 'is_taxable_afc')) {
                        $item->is_taxable_afc = (bool) $lre->is_taxable_afc;
                    }
                    if (Schema::hasColumn('items', 'is_severance_base')) {
                        $item->is_severance_base = (bool) $lre->is_severance_base;
                    }
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Concepto normativo del Libro de Remuneraciones Electrónico (LRE DT).
     */
    public function lreConcept(): BelongsTo
    {
        return $this->belongsTo(LreConcept::class, 'lre_concept_id');
    }

    public function employeeItems(): HasMany
    {
        return $this->hasMany(EmployeeItem::class, 'item_id');
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
        return self::$typeLabels[$this->type] ?? (string) $this->type;
    }

    public function getCalcLabelAttribute(): string
    {
        return self::$calcLabels[$this->calculation_type] ?? (string) $this->calculation_type;
    }

    public function getAssignLabelAttribute(): string
    {
        return self::$assignLabels[$this->assignment_type] ?? (string) $this->assignment_type;
    }
}
