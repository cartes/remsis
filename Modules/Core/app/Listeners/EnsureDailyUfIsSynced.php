<?php

namespace Modules\Core\Listeners;

use Illuminate\Auth\Events\Login;
use Modules\Core\Services\EconomicIndicatorSyncService;

class EnsureDailyUfIsSynced
{
    public function __construct(private EconomicIndicatorSyncService $syncService) {}

    public function handle(Login $event): void
    {
        $this->syncService->ensureTodayUfIsAvailable();
    }
}
