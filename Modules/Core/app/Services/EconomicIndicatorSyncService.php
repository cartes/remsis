<?php

namespace Modules\Core\Services;

use Carbon\CarbonImmutable;
use Modules\AdminPanel\Models\LegalParameter;
use Modules\Core\Models\BcchCredential;
use Modules\Core\Models\DailyUfValue;
use Modules\Core\Models\MonthlyIpcValue;
use RuntimeException;
use Throwable;

class EconomicIndicatorSyncService
{
    public function __construct(private BancoCentralApiService $api) {}

    public function ensureTodayUfIsAvailable(bool $force = false): array
    {
        $today = CarbonImmutable::now('America/Santiago')->startOfDay();
        $todayUf = DailyUfValue::query()
            ->with('monthlyIpcValue')
            ->whereDate('date', $today->toDateString())
            ->first();

        if ($todayUf) {
            $this->upsertUfLegalParameter((float) $todayUf->value);

            if ($todayUf->monthlyIpcValue) {
                $this->upsertIpcLegalParameter(
                    (float) $this->canonicalizeMonthlyIpcValue($todayUf->monthlyIpcValue)->value
                );
            }

            return [
                'status' => 'already_available',
                'message' => 'La UF del día ya se encuentra disponible localmente.',
                'value' => $todayUf->value,
            ];
        }

        $credential = BcchCredential::query()->first();

        if (! $credential || blank($credential->username) || blank($credential->password)) {
            return [
                'status' => 'missing_credentials',
                'message' => 'No hay credenciales configuradas para sincronizar la UF del día.',
            ];
        }

        if (! $force && $credential->last_daily_sync_attempted_for?->isSameDay($today)) {
            return [
                'status' => 'already_attempted_today',
                'message' => 'La sincronización automática de UF ya fue intentada hoy.',
            ];
        }

        $credential->forceFill([
            'last_daily_sync_attempted_for' => $today->toDateString(),
            'last_daily_sync_attempted_at' => now(),
            'last_daily_sync_error' => null,
        ])->save();

        try {
            [$cycleStart, $cycleEnd, $referencePeriod] = $this->resolveActiveCycle($today);
            $ufData = $this->api->fetchUfValue($credential->username, $credential->password, $today);
            [$monthlyIpc, $usedCachedIpc] = $this->resolveMonthlyIpcForDailySync(
                $credential,
                $referencePeriod,
            );

            $todayUf = DailyUfValue::query()->updateOrCreate(
                ['date' => $today->toDateString()],
                [
                    'monthly_ipc_value_id' => $monthlyIpc->id,
                    'cycle_start_date' => $cycleStart->toDateString(),
                    'cycle_end_date' => $cycleEnd->toDateString(),
                    'value' => number_format((float) $ufData['value'], 2, '.', ''),
                ],
            );

            if ($monthlyIpc) {
                $this->upsertIpcLegalParameter((float) $monthlyIpc->value);
            }

            $this->upsertUfLegalParameter((float) $todayUf->value);

            $credential->forceFill([
                'last_daily_sync_succeeded_at' => now(),
                'last_daily_sync_error' => null,
            ])->save();

            return [
                'status' => $usedCachedIpc ? 'synced_with_cached_ipc' : 'synced',
                'message' => $usedCachedIpc
                    ? 'La UF del día fue sincronizada correctamente. El IPC mensual se mantuvo con el valor local disponible.'
                    : 'La UF del día fue sincronizada correctamente.',
                'value' => $todayUf->value,
            ];
        } catch (Throwable $exception) {
            $credential->forceFill([
                'last_daily_sync_error' => $exception->getMessage(),
            ])->save();

            return [
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function sync(?CarbonImmutable $referenceDate = null, ?CarbonImmutable $cycleStart = null): array
    {
        $referenceDate ??= CarbonImmutable::now('America/Santiago')->startOfDay();
        $cycleStart ??= $this->resolveCycleStart($referenceDate);
        $cycleEnd = $cycleStart->addMonthNoOverflow()->day(9)->startOfDay();
        $referencePeriod = $cycleStart->subMonthNoOverflow()->startOfMonth();

        $credential = BcchCredential::query()->first();

        if (! $credential || blank($credential->username) || blank($credential->password)) {
            throw new RuntimeException('Debes configurar las credenciales del Banco Central antes de sincronizar indicadores.');
        }

        $ipcData = $this->api->fetchMonthlyIpcVariation($credential->username, $credential->password, $referencePeriod);

        $monthlyIpc = MonthlyIpcValue::query()->updateOrCreate(
            [
                'year' => $referencePeriod->year,
                'month' => $referencePeriod->month,
            ],
            [
                'reference_period' => $referencePeriod->toDateString(),
                'published_at' => $ipcData['published_at']->toDateString(),
                'value' => $ipcData['value'],
                'source_series' => $ipcData['series_id'],
            ],
        );
        $monthlyIpc = $this->canonicalizeMonthlyIpcValue($monthlyIpc);

        $baseUfDate = $cycleStart->subDay();
        $baseUf = DailyUfValue::query()
            ->whereDate('date', $baseUfDate->toDateString())
            ->value('value');

        if ($baseUf === null) {
            $baseUf = $this->api->fetchUfValue($credential->username, $credential->password, $baseUfDate)['value'];
        }

        $records = $this->calculateDailyUfValues(
            $cycleStart,
            $cycleEnd,
            (float) $baseUf,
            (float) $monthlyIpc->value,
            $monthlyIpc->id,
        );

        DailyUfValue::query()->upsert(
            $records,
            ['date'],
            ['monthly_ipc_value_id', 'cycle_start_date', 'cycle_end_date', 'value', 'updated_at'],
        );

        $this->syncLegalParameters($credential, $referenceDate, (float) $monthlyIpc->value);

        return [
            'reference_period' => $referencePeriod,
            'cycle_start' => $cycleStart,
            'cycle_end' => $cycleEnd,
            'ipc' => $this->normalizePublishedIpc((float) $monthlyIpc->value),
            'days_synced' => count($records),
            'first_uf' => $records[0]['value'] ?? null,
            'last_uf' => $records[array_key_last($records)]['value'] ?? null,
        ];
    }

    private function calculateDailyUfValues(
        CarbonImmutable $cycleStart,
        CarbonImmutable $cycleEnd,
        float $baseUf,
        float $ipc,
        int $monthlyIpcValueId,
    ): array {
        $days = $cycleStart->diffInDays($cycleEnd) + 1;
        $factor = pow(1 + ($ipc / 100), 1 / $days);
        $runningUf = round($baseUf, 2);
        $records = [];
        $now = now();

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $cycleStart->addDays($offset);
            $runningUf = round($runningUf * $factor, 2);

            $records[] = [
                'monthly_ipc_value_id' => $monthlyIpcValueId,
                'date' => $date->toDateString(),
                'cycle_start_date' => $cycleStart->toDateString(),
                'cycle_end_date' => $cycleEnd->toDateString(),
                'value' => number_format($runningUf, 2, '.', ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $records;
    }

    private function syncLegalParameters(BcchCredential $credential, CarbonImmutable $referenceDate, float $ipc): void
    {
        $this->upsertIpcLegalParameter($ipc);

        $currentUf = DailyUfValue::query()
            ->whereDate('date', $referenceDate->toDateString())
            ->value('value');

        if ($currentUf === null) {
            $currentUf = DailyUfValue::query()
                ->whereDate('date', '<=', $referenceDate->toDateString())
                ->orderByDesc('date')
                ->value('value');
        }

        if ($currentUf === null) {
            $currentUf = $this->api->fetchUfValue($credential->username, $credential->password, $referenceDate)['value'];
        }

        $this->upsertUfLegalParameter((float) $currentUf);
    }

    private function upsertIpcLegalParameter(float $ipc): void
    {
        $normalizedIpc = $this->normalizePublishedIpc($ipc);

        LegalParameter::query()->updateOrCreate(
            ['key' => 'ipc_value'],
            [
                'value' => number_format($normalizedIpc, 1, '.', ''),
                'label' => 'IPC (%)',
                'description' => 'Variación porcentual del Índice de Precios al Consumidor.',
            ],
        );
    }

    private function upsertUfLegalParameter(float $uf): void
    {
        LegalParameter::query()->updateOrCreate(
            ['key' => 'uf_value'],
            [
                'value' => number_format($uf, 2, '.', ''),
                'label' => 'Valor UF',
                'description' => 'Unidad de Fomento (UF) vigente.',
            ],
        );
    }

    private function resolveCycleStart(CarbonImmutable $referenceDate): CarbonImmutable
    {
        if ($referenceDate->day <= 9) {
            return $referenceDate->startOfMonth()->day(10)->startOfDay();
        }

        return $referenceDate->addMonthNoOverflow()->startOfMonth()->day(10)->startOfDay();
    }

    private function resolveActiveCycle(CarbonImmutable $referenceDate): array
    {
        if ($referenceDate->day >= 10) {
            $cycleStart = $referenceDate->startOfMonth()->day(10)->startOfDay();
            $cycleEnd = $referenceDate->addMonthNoOverflow()->startOfMonth()->day(9)->startOfDay();
        } else {
            $cycleStart = $referenceDate->subMonthNoOverflow()->startOfMonth()->day(10)->startOfDay();
            $cycleEnd = $referenceDate->startOfMonth()->day(9)->startOfDay();
        }

        return [
            $cycleStart,
            $cycleEnd,
            $cycleStart->subMonthNoOverflow()->startOfMonth(),
        ];
    }

    private function resolveMonthlyIpcForDailySync(
        BcchCredential $credential,
        CarbonImmutable $referencePeriod,
    ): array {
        $existing = MonthlyIpcValue::query()
            ->where('year', $referencePeriod->year)
            ->where('month', $referencePeriod->month)
            ->first();

        if ($existing) {
            return [$this->canonicalizeMonthlyIpcValue($existing), false];
        }

        try {
            $ipcData = $this->api->fetchMonthlyIpcVariation($credential->username, $credential->password, $referencePeriod);

            return [
                $this->canonicalizeMonthlyIpcValue(MonthlyIpcValue::query()->updateOrCreate(
                    [
                        'year' => $referencePeriod->year,
                        'month' => $referencePeriod->month,
                    ],
                    [
                        'reference_period' => $referencePeriod->toDateString(),
                        'published_at' => $ipcData['published_at']->toDateString(),
                        'value' => $ipcData['value'],
                        'source_series' => $ipcData['series_id'],
                    ],
                )),
                false,
            ];
        } catch (Throwable $exception) {
            $cachedIpc = LegalParameter::query()->where('key', 'ipc_value')->value('value');

            if (! is_numeric($cachedIpc)) {
                throw $exception;
            }

            $normalizedCachedIpc = $this->normalizePublishedIpc((float) $cachedIpc);

            return [
                MonthlyIpcValue::query()->updateOrCreate(
                    [
                        'year' => $referencePeriod->year,
                        'month' => $referencePeriod->month,
                    ],
                    [
                        'reference_period' => $referencePeriod->toDateString(),
                        'published_at' => null,
                        'value' => number_format($normalizedCachedIpc, 4, '.', ''),
                        'source_series' => 'legal_parameters_cache',
                    ],
                ),
                true,
            ];
        }
    }

    private function canonicalizeMonthlyIpcValue(MonthlyIpcValue $monthlyIpc): MonthlyIpcValue
    {
        $normalizedValue = number_format($this->normalizePublishedIpc((float) $monthlyIpc->value), 4, '.', '');

        if ((string) $monthlyIpc->value !== $normalizedValue) {
            $monthlyIpc->forceFill([
                'value' => $normalizedValue,
            ])->save();
        }

        return $monthlyIpc->refresh();
    }

    private function normalizePublishedIpc(float $value): float
    {
        $rounded = round($value, 1);

        return $rounded == 0.0 ? 0.0 : $rounded;
    }
}
