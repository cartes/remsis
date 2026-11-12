<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo global de indicadores previsionales y tributarios mensuales (UF, UTM, IMM, Topes, AFP, IUT y Cargas).
 * No lleva tenant_id por tratarse de un catálogo normativo nacional compartido.
 */
class MonthlyIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'year',
        'month',
        'uf_value',
        'uf_previous_month',
        'utm_value',
        'uta_value',
        'ipc_value',
        'imm_value',
        'imm_under_18_over_65',
        'imm_non_remunerational',
        'cap_afp_uf',
        'cap_ips_uf',
        'cap_afc_uf',
        'sis_rate',
        'afc_indefinite_employee_rate',
        'afc_indefinite_employer_rate',
        'afc_fixed_employer_rate',
        'mutual_base_rate',
        'sanna_rate',
        'cesantia_pensionados_employer_rate',
        'legal_weekly_hours',
        'afp_rates',
        'iut_brackets',
        'family_allowance_brackets',
        'source',
        'synced_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'uf_value' => 'float',
        'uf_previous_month' => 'float',
        'utm_value' => 'float',
        'uta_value' => 'float',
        'ipc_value' => 'float',
        'imm_value' => 'float',
        'imm_under_18_over_65' => 'float',
        'imm_non_remunerational' => 'float',
        'cap_afp_uf' => 'float',
        'cap_ips_uf' => 'float',
        'cap_afc_uf' => 'float',
        'sis_rate' => 'float',
        'afc_indefinite_employee_rate' => 'float',
        'afc_indefinite_employer_rate' => 'float',
        'afc_fixed_employer_rate' => 'float',
        'mutual_base_rate' => 'float',
        'sanna_rate' => 'float',
        'cesantia_pensionados_employer_rate' => 'float',
        'legal_weekly_hours' => 'integer',
        'afp_rates' => 'array',
        'iut_brackets' => 'array',
        'family_allowance_brackets' => 'array',
        'synced_at' => 'datetime',
    ];

    public function afpRateRecords(): HasMany
    {
        return $this->hasMany(MonthlyAfpRate::class, 'monthly_indicator_id');
    }

    public function iutBracketRecords(): HasMany
    {
        return $this->hasMany(MonthlyIutBracket::class, 'monthly_indicator_id')->orderBy('bracket_number');
    }

    public function familyAllowanceBracketRecords(): HasMany
    {
        return $this->hasMany(MonthlyFamilyAllowanceBracket::class, 'monthly_indicator_id')->orderBy('bracket_letter');
    }

    /**
     * Obtiene la tasa total de cotización para una AFP dada (10% obligatorio + comisión).
     */
    public function getAfpRate(string $afpCodeOrName): float
    {
        $normalizedKey = mb_strtolower(trim($afpCodeOrName));

        if (is_array($this->afp_rates)) {
            foreach ($this->afp_rates as $key => $rateData) {
                $code = mb_strtolower((string) ($rateData['afp_code'] ?? $key));
                $name = mb_strtolower((string) ($rateData['afp_name'] ?? ''));

                if ($code === $normalizedKey || $name === $normalizedKey || str_contains($name, $normalizedKey)) {
                    return (float) ($rateData['total_rate'] ?? ((float) ($rateData['mandatory_rate'] ?? 10.0) + (float) ($rateData['commission_rate'] ?? 0.0)));
                }
            }
        }

        $record = $this->afpRateRecords()
            ->whereRaw('LOWER(afp_code) = ?', [$normalizedKey])
            ->orWhereRaw('LOWER(afp_name) = ?', [$normalizedKey])
            ->first();

        return $record ? (float) $record->total_rate : 10.0;
    }

    /**
     * Calcula el Impuesto Único de Segunda Categoría (IUSC) en CLP redondeado a 0 decimales.
     * En cálculos intermedios en UTM se mantiene precisión de 6 decimales.
     */
    public function calculateIut(float $taxableBaseClp): float
    {
        if ($taxableBaseClp <= 0 || $this->utm_value <= 0) {
            return 0.0;
        }

        $taxableUtm = round($taxableBaseClp / $this->utm_value, 6);
        $brackets = is_array($this->iut_brackets) && ! empty($this->iut_brackets)
            ? $this->iut_brackets
            : $this->iutBracketRecords()->get()->toArray();

        foreach ($brackets as $bracket) {
            $fromUtm = (float) ($bracket['from_utm'] ?? 0.0);
            $toUtm = isset($bracket['to_utm']) && $bracket['to_utm'] !== null ? (float) $bracket['to_utm'] : null;

            $matchesLower = $taxableUtm > $fromUtm || ($fromUtm === 0.0 && $taxableUtm >= 0.0);
            $matchesUpper = $toUtm === null || $taxableUtm <= $toUtm;

            if ($matchesLower && $matchesUpper) {
                $factor = (float) ($bracket['factor'] ?? 0.0);
                $deductionUtm = (float) ($bracket['deduction_utm'] ?? 0.0);

                if ($factor <= 0.0) {
                    return 0.0;
                }

                $taxInUtm = ($taxableUtm * $factor) - $deductionUtm;
                $taxInClp = $taxInUtm * $this->utm_value;

                return max(0.0, round($taxInClp, 0));
            }
        }

        return 0.0;
    }

    /**
     * Obtiene el monto en CLP por carga familiar según el ingreso imponible del trabajador.
     */
    public function getFamilyAllowancePerDependent(float $incomeClp): float
    {
        $brackets = is_array($this->family_allowance_brackets) && ! empty($this->family_allowance_brackets)
            ? $this->family_allowance_brackets
            : $this->familyAllowanceBracketRecords()->get()->toArray();

        foreach ($brackets as $bracket) {
            $from = (float) ($bracket['income_from'] ?? 0.0);
            $to = isset($bracket['income_to']) && $bracket['income_to'] !== null ? (float) $bracket['income_to'] : null;

            if ($incomeClp >= $from && ($to === null || $incomeClp <= $to)) {
                return round((float) ($bracket['amount_per_dependent'] ?? 0.0), 0);
            }
        }

        return 0.0;
    }

    /**
     * Calcula el tope imponible de AFP/Salud en CLP (ej. 84.3 UF * UF último día mes anterior), redondeado a 0 decimales.
     */
    public function getAfpCapClp(): float
    {
        $ufForCap = $this->uf_previous_month > 0 ? $this->uf_previous_month : $this->uf_value;

        return round($this->cap_afp_uf * $ufForCap, 0);
    }

    /**
     * Calcula el tope imponible de Seguro de Cesantía (AFC) en CLP (ej. 126.6 UF * UF último día mes anterior), redondeado a 0 decimales.
     */
    public function getAfcCapClp(): float
    {
        $ufForCap = $this->uf_previous_month > 0 ? $this->uf_previous_month : $this->uf_value;

        return round($this->cap_afc_uf * $ufForCap, 0);
    }

    /**
     * Calcula el tope mensual de Gratificación Legal Art. 50 CT (4.75 IMM / 12), redondeado a 0 decimales.
     */
    public function getGratificationCapClp(): float
    {
        return round(($this->imm_value * 4.75) / 12, 0);
    }
}

