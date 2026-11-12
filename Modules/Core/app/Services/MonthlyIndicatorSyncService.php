<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\LegalParameter;
use Modules\Core\Models\DailyUfValue;
use Modules\Core\Models\MonthlyIndicator;
use Modules\Core\Models\MonthlyIpcValue;
use RuntimeException;
use Throwable;

/**
 * Servicio orquestador para sincronizar y persistir los indicadores previsionales mensuales
 * en `monthly_indicators` y sus tablas relacionales (`monthly_afp_rates`, `monthly_iut_brackets`,
 * `monthly_family_allowance_brackets`).
 */
class MonthlyIndicatorSyncService
{
    public function __construct(private MindicadorApiService $mindicadorApi)
    {
    }

    /**
     * Sincroniza los indicadores para un período mensual en formato YYYY-MM.
     */
    public function syncPeriod(?string $period = null): MonthlyIndicator
    {
        $targetDate = $period
            ? CarbonImmutable::createFromFormat('Y-m', $period, 'America/Santiago')->startOfMonth()
            : CarbonImmutable::now('America/Santiago')->startOfMonth();

        if (!$targetDate) {
            throw new RuntimeException('El período especificado no es válido. Use el formato YYYY-MM.');
        }

        $periodKey = $targetDate->format('Y-m');
        $endOfMonth = $targetDate->endOfMonth();
        $endOfPreviousMonth = $targetDate->subMonth()->endOfMonth();

        [$ufValue, $ufPreviousMonth, $utmValue, $ipcValue, $source] = $this->resolveEconomicValues(
            $targetDate,
            $endOfMonth,
            $endOfPreviousMonth
        );

        $afpRates = $this->buildAfpRatesPayload();
        $iutBrackets = $this->buildDefaultIutBrackets();
        $familyBrackets = $this->buildDefaultFamilyAllowanceBrackets();

        $legalWeeklyHours = (int) $targetDate->format('Y') >= 2028
            ? 40
            : ((int) $targetDate->format('Y') >= 2026 ? 42 : 44);

        return DB::transaction(function () use ($periodKey, $targetDate, $ufValue, $ufPreviousMonth, $utmValue, $ipcValue, $legalWeeklyHours, $afpRates, $iutBrackets, $familyBrackets, $source): MonthlyIndicator {
            $indicator = MonthlyIndicator::query()->updateOrCreate(
                ['period' => $periodKey],
                [
                    'year' => (int) $targetDate->format('Y'),
                    'month' => (int) $targetDate->format('n'),
                    'uf_value' => round($ufValue, 2),
                    'uf_previous_month' => round($ufPreviousMonth, 2),
                    'utm_value' => round($utmValue, 2),
                    'uta_value' => round($utmValue * 12, 2),
                    'ipc_value' => round($ipcValue, 2),
                    'imm_value' => 510636,
                    'imm_under_18_over_65' => 380923,
                    'imm_non_remunerational' => 329151,
                    'cap_afp_uf' => 84.30,
                    'cap_ips_uf' => 60.00,
                    'cap_afc_uf' => 126.60,
                    'sis_rate' => 1.49,
                    'afc_indefinite_employee_rate' => 0.60,
                    'afc_indefinite_employer_rate' => 2.40,
                    'afc_fixed_employer_rate' => 3.00,
                    'mutual_base_rate' => 0.90,
                    'sanna_rate' => 0.03,
                    'cesantia_pensionados_employer_rate' => 0.10,
                    'legal_weekly_hours' => $legalWeeklyHours,
                    'afp_rates' => $afpRates,
                    'iut_brackets' => $iutBrackets,
                    'family_allowance_brackets' => $familyBrackets,
                    'source' => $source,
                    'synced_at' => now(),
                ]
            );

            $this->syncRelationalDetails($indicator, $afpRates, $iutBrackets, $familyBrackets);
            $this->syncLegacyLegalParameters($indicator);

            return $indicator->fresh([
                'afpRateRecords',
                'iutBracketRecords',
                'familyAllowanceBracketRecords',
            ]);
        });
    }

    /**
     * Resuelve UF del mes, UF del mes anterior, UTM e IPC desde mindicador.cl o fallback local.
     *
     * @return array{0: float, 1: float, 2: float, 3: float, 4: string}
     */
    protected function resolveEconomicValues(
        CarbonImmutable $targetDate,
        CarbonImmutable $endOfMonth,
        CarbonImmutable $endOfPreviousMonth
    ): array {
        $source = 'mindicador';
        $ufValue = null;
        $ufPreviousMonth = null;
        $utmValue = null;
        $ipcValue = null;

        try {
            $todaySummary = $this->mindicadorApi->getTodayIndicators();
            $ufValue = $todaySummary['uf'];
            $utmValue = $todaySummary['utm'];
            $ipcValue = $todaySummary['ipc'];
        } catch (Throwable) {
            $source = 'local_fallback';
        }

        try {
            $ufValue = $this->mindicadorApi->getIndicatorForDate('uf', $endOfMonth);
        } catch (Throwable) {
            // Mantiene el valor diario o procede a fallback local
        }

        try {
            $ufPreviousMonth = $this->mindicadorApi->getIndicatorForDate('uf', $endOfPreviousMonth);
        } catch (Throwable) {
            // Procede a fallback local
        }

        try {
            $utmValue = $this->mindicadorApi->getIndicatorForDate('utm', $targetDate);
        } catch (Throwable) {
            // Mantiene el valor previo o procede a fallback local
        }

        if ($ufValue === null || $ufValue <= 0) {
            $localUf = DailyUfValue::query()
                ->whereDate('date', '<=', $endOfMonth->toDateString())
                ->orderByDesc('date')
                ->value('value');

            $ufValue = $localUf !== null ? (float) $localUf : 38500.00;
        }

        if ($ufPreviousMonth === null || $ufPreviousMonth <= 0) {
            $localPrevUf = DailyUfValue::query()
                ->whereDate('date', '<=', $endOfPreviousMonth->toDateString())
                ->orderByDesc('date')
                ->value('value');

            $ufPreviousMonth = $localPrevUf !== null ? (float) $localPrevUf : $ufValue;
        }

        if ($utmValue === null || $utmValue <= 0) {
            $utmValue = 68034.00;
        }

        if ($ipcValue === null) {
            $localIpc = MonthlyIpcValue::query()
                ->orderByDesc('reference_period')
                ->value('value');

            $ipcValue = $localIpc !== null ? (float) $localIpc : 0.40;
        }

        return [$ufValue, $ufPreviousMonth, $utmValue, $ipcValue, $source];
    }

    /**
     * Construye el catálogo de tasas AFP del período (10% obligatorio + comisión de cada AFP).
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildAfpRatesPayload(): array
    {
        $defaultAfps = [
            ['afp_code' => 'capital', 'afp_name' => 'AFP Capital', 'mandatory_rate' => 10.00, 'commission_rate' => 1.44],
            ['afp_code' => 'cuprum', 'afp_name' => 'AFP Cuprum', 'mandatory_rate' => 10.00, 'commission_rate' => 1.44],
            ['afp_code' => 'habitat', 'afp_name' => 'AFP Habitat', 'mandatory_rate' => 10.00, 'commission_rate' => 1.27],
            ['afp_code' => 'modelo', 'afp_name' => 'AFP Modelo', 'mandatory_rate' => 10.00, 'commission_rate' => 0.58],
            ['afp_code' => 'planvital', 'afp_name' => 'AFP PlanVital', 'mandatory_rate' => 10.00, 'commission_rate' => 1.16],
            ['afp_code' => 'provida', 'afp_name' => 'AFP ProVida', 'mandatory_rate' => 10.00, 'commission_rate' => 1.45],
            ['afp_code' => 'uno', 'afp_name' => 'AFP Uno', 'mandatory_rate' => 10.00, 'commission_rate' => 0.49],
        ];

        if (Schema::hasTable('afps') && Afp::query()->exists()) {
            $dbAfps = Afp::query()->get();
            if ($dbAfps->isNotEmpty()) {
                return $dbAfps->map(static function (Afp $afp): array {
                    $mandatory = (float) ($afp->rate > 0 ? $afp->rate : 10.00);
                    $commission = (float) ($afp->commission ?? 0.00);
                    $total = round($mandatory + $commission, 2);

                    return [
                        'afp_code' => (string) ($afp->code ?: mb_strtolower(str_replace(' ', '_', $afp->nombre))),
                        'afp_name' => (string) $afp->nombre,
                        'mandatory_rate' => $mandatory,
                        'commission_rate' => $commission,
                        'total_rate' => $total,
                        'independent_rate' => round($total + 1.49, 2),
                    ];
                })->values()->all();
            }
        }

        return array_map(static function (array $item): array {
            $total = round($item['mandatory_rate'] + $item['commission_rate'], 2);
            $item['total_rate'] = $total;
            $item['independent_rate'] = round($total + 1.49, 2);

            return $item;
        }, $defaultAfps);
    }

    /**
     * Tabla oficial de 8 tramos del Impuesto Único de Segunda Categoría (IUSC) del SII en UTM.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildDefaultIutBrackets(): array
    {
        return [
            [
                'bracket_number' => 1,
                'from_utm' => 0.0000,
                'to_utm' => 13.5000,
                'factor' => 0.000000,
                'deduction_utm' => 0.0000,
                'max_effective_rate' => 0.0000,
            ],
            [
                'bracket_number' => 2,
                'from_utm' => 13.5000,
                'to_utm' => 30.0000,
                'factor' => 0.040000,
                'deduction_utm' => 0.5400,
                'max_effective_rate' => 0.0220,
            ],
            [
                'bracket_number' => 3,
                'from_utm' => 30.0000,
                'to_utm' => 50.0000,
                'factor' => 0.080000,
                'deduction_utm' => 1.7400,
                'max_effective_rate' => 0.0452,
            ],
            [
                'bracket_number' => 4,
                'from_utm' => 50.0000,
                'to_utm' => 70.0000,
                'factor' => 0.135000,
                'deduction_utm' => 4.4900,
                'max_effective_rate' => 0.0709,
            ],
            [
                'bracket_number' => 5,
                'from_utm' => 70.0000,
                'to_utm' => 90.0000,
                'factor' => 0.230000,
                'deduction_utm' => 11.1400,
                'max_effective_rate' => 0.1062,
            ],
            [
                'bracket_number' => 6,
                'from_utm' => 90.0000,
                'to_utm' => 120.0000,
                'factor' => 0.304000,
                'deduction_utm' => 17.8000,
                'max_effective_rate' => 0.1557,
            ],
            [
                'bracket_number' => 7,
                'from_utm' => 120.0000,
                'to_utm' => 310.0000,
                'factor' => 0.350000,
                'deduction_utm' => 23.3200,
                'max_effective_rate' => 0.2748,
            ],
            [
                'bracket_number' => 8,
                'from_utm' => 310.0000,
                'to_utm' => null,
                'factor' => 0.400000,
                'deduction_utm' => 38.8200,
                'max_effective_rate' => null,
            ],
        ];
    }

    /**
     * Tabla oficial de 4 tramos de Asignación Familiar (DFL N° 150) en CLP.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildDefaultFamilyAllowanceBrackets(): array
    {
        return [
            [
                'bracket_letter' => 'A',
                'income_from' => 0,
                'income_to' => 598698,
                'amount_per_dependent' => 21243,
            ],
            [
                'bracket_letter' => 'B',
                'income_from' => 598699,
                'income_to' => 874460,
                'amount_per_dependent' => 13036,
            ],
            [
                'bracket_letter' => 'C',
                'income_from' => 874461,
                'income_to' => 1363858,
                'amount_per_dependent' => 4119,
            ],
            [
                'bracket_letter' => 'D',
                'income_from' => 1363859,
                'income_to' => null,
                'amount_per_dependent' => 0,
            ],
        ];
    }

    /**
     * Sincroniza las tablas relacionales hijas para auditoría y consultas SQL directas.
     */
    protected function syncRelationalDetails(
        MonthlyIndicator $indicator,
        array $afpRates,
        array $iutBrackets,
        array $familyBrackets
    ): void {
        foreach ($afpRates as $rate) {
            $indicator->afpRateRecords()->updateOrCreate(
                ['afp_code' => $rate['afp_code']],
                $rate
            );
        }

        foreach ($iutBrackets as $bracket) {
            $indicator->iutBracketRecords()->updateOrCreate(
                ['bracket_number' => $bracket['bracket_number']],
                $bracket
            );
        }

        foreach ($familyBrackets as $family) {
            $indicator->familyAllowanceBracketRecords()->updateOrCreate(
                ['bracket_letter' => $family['bracket_letter']],
                $family
            );
        }
    }

    /**
     * Mantiene sincronizados los registros legacy en `legal_parameters` para compatibilidad con vistas actuales.
     */
    protected function syncLegacyLegalParameters(MonthlyIndicator $indicator): void
    {
        if (!Schema::hasTable('legal_parameters')) {
            return;
        }

        LegalParameter::query()->updateOrCreate(
            ['key' => 'uf_value'],
            [
                'value' => number_format($indicator->uf_value, 2, '.', ''),
                'label' => 'Valor UF ($)',
                'description' => 'Valor de la Unidad de Fomento sincronizado en indicadores mensuales.',
            ]
        );

        LegalParameter::query()->updateOrCreate(
            ['key' => 'utm_value'],
            [
                'value' => number_format($indicator->utm_value, 2, '.', ''),
                'label' => 'Valor UTM ($)',
                'description' => 'Valor de la Unidad Tributaria Mensual del período.',
            ]
        );

        LegalParameter::query()->updateOrCreate(
            ['key' => 'imm_value'],
            [
                'value' => number_format($indicator->imm_value, 0, '.', ''),
                'label' => 'Ingreso Mínimo Mensual ($)',
                'description' => 'Sueldo mínimo legal vigente.',
            ]
        );
    }
}