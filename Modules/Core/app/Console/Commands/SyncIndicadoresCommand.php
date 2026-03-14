<?php

namespace Modules\Core\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\Core\Services\EconomicIndicatorSyncService;
use Throwable;

class SyncIndicadoresCommand extends Command
{
    protected $signature = 'remsis:sync-indicadores
                            {--for-date= : Fecha de referencia para resolver el ciclo legal (Y-m-d)}
                            {--cycle-start= : Fecha exacta de inicio del ciclo a recalcular (Y-m-d)}';

    protected $description = 'Sincroniza IPC mensual y calcula la UF diaria según el ciclo legal chileno 10-9.';

    public function __construct(private EconomicIndicatorSyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $referenceDate = $this->option('for-date')
                ? CarbonImmutable::parse((string) $this->option('for-date'), 'America/Santiago')->startOfDay()
                : null;

            $cycleStart = $this->option('cycle-start')
                ? CarbonImmutable::parse((string) $this->option('cycle-start'), 'America/Santiago')->startOfDay()
                : null;

            $result = $this->syncService->sync($referenceDate, $cycleStart);

            $this->info(sprintf(
                'IPC %.1f%% sincronizado. Ciclo UF %s -> %s (%d días). UF inicial %s / UF final %s.',
                $result['ipc'],
                $result['cycle_start']->format('Y-m-d'),
                $result['cycle_end']->format('Y-m-d'),
                $result['days_synced'],
                $result['first_uf'],
                $result['last_uf'],
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
