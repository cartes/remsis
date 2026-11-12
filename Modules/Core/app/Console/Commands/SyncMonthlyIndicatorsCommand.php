<?php

declare(strict_types=1);

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Services\MonthlyIndicatorSyncService;
use Throwable;

class SyncMonthlyIndicatorsCommand extends Command
{
    protected $signature = 'remsis:sync-monthly-indicators
                            {--period= : Período YYYY-MM a sincronizar (por defecto mes actual)}';

    protected $description = 'Sincroniza los indicadores previsionales mensuales (UF, UTM, IMM, Tasas AFP, Tramos IUT y Asignación Familiar) desde mindicador.cl / CMF.';

    public function __construct(private MonthlyIndicatorSyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $period = $this->option('period') ? (string) $this->option('period') : null;
            $indicator = $this->syncService->syncPeriod($period);

            $this->info(sprintf(
                'Indicadores mensuales sincronizados para %s (Fuente: %s). UF: %s | UTM: %s | IMM: %s.',
                $indicator->period,
                $indicator->source,
                number_format($indicator->uf_value, 2, ',', '.'),
                number_format($indicator->utm_value, 0, ',', '.'),
                number_format($indicator->imm_value, 0, ',', '.')
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}

