<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Concepto oficial del Libro de Remuneraciones Electrónico (LRE) de la Dirección del Trabajo (DT).
 * Define el tratamiento previsional, tributario y laboral según los Art. 41, 42 y 58 del Código del Trabajo.
 */
class LreConcept extends Model
{
    use HasFactory;

    public const CLASSIFICATION_TAXABLE_EARNING = 'haber_imponible';

    public const CLASSIFICATION_NON_TAXABLE_EARNING = 'haber_no_imponible';

    public const CLASSIFICATION_LEGAL_DEDUCTION = 'descuento_legal';

    public const CLASSIFICATION_JUDICIAL_DEDUCTION = 'descuento_judicial';

    public const CLASSIFICATION_VOLUNTARY_DEDUCTION = 'descuento_voluntario';

    public const CLASSIFICATION_EMPLOYER_CONTRIBUTION = 'aporte_empleador';

    protected $table = 'lre_concepts_dictionary';

    protected $fillable = [
        'code',
        'name',
        'classification',
        'legal_article',
        'is_taxable',
        'is_taxable_afc',
        'is_tributable',
        'is_gratification_base',
        'is_overtime_base',
        'is_severance_base',
        'previred_code',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_taxable_afc' => 'boolean',
        'is_tributable' => 'boolean',
        'is_gratification_base' => 'boolean',
        'is_overtime_base' => 'boolean',
        'is_severance_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeTaxable(Builder $query): Builder
    {
        return $query->where('is_taxable', true);
    }

    public function scopeNonTaxable(Builder $query): Builder
    {
        return $query->where('classification', self::CLASSIFICATION_NON_TAXABLE_EARNING);
    }

    public function scopeLegalDeductions(Builder $query): Builder
    {
        return $query->where('classification', self::CLASSIFICATION_LEGAL_DEDUCTION);
    }
}

