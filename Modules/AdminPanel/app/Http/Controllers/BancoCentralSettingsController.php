<?php

namespace Modules\AdminPanel\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\BcchCredential;
use Modules\Core\Models\DailyUfValue;
use Modules\Core\Models\MonthlyIpcValue;
use Modules\Core\Services\EconomicIndicatorSyncService;

class BancoCentralSettingsController extends Controller
{
    public function index()
    {
        $today = CarbonImmutable::now('America/Santiago')->startOfDay();
        $monthStart = $today->startOfMonth();
        $monthEnd = $today->endOfMonth();
        $credential = BcchCredential::query()->first();
        $todayUf = DailyUfValue::query()->whereDate('date', $today->toDateString())->first();
        $attemptedToday = $credential?->last_daily_sync_attempted_for?->isSameDay($today) ?? false;

        return view('adminpanel::settings.bcch', [
            'credential' => $credential,
            'latestIpc' => MonthlyIpcValue::query()->latest('reference_period')->first(),
            'ipcHistory' => MonthlyIpcValue::query()->latest('reference_period')->limit(12)->get(),
            'currentMonthUfValues' => DailyUfValue::query()
                ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->orderBy('date')
                ->get(),
            'todayUf' => $todayUf,
            'showManualSyncTodayButton' => auth()->user()?->hasRole('super-admin')
                && $todayUf === null
                && $credential !== null
                && filled($credential->username)
                && filled($credential->password),
            'attemptedToday' => $attemptedToday,
            'activeCycleSample' => DailyUfValue::query()
                ->whereDate('cycle_start_date', '<=', $today->toDateString())
                ->whereDate('cycle_end_date', '>=', $today->toDateString())
                ->orderBy('date')
                ->first()
                ?? DailyUfValue::query()->latest('cycle_start_date')->first(),
        ]);
    }

    public function updateCredentials(Request $request): RedirectResponse
    {
        $credential = BcchCredential::query()->first();

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => [$credential ? 'nullable' : 'required', 'string', 'max:255'],
        ]);

        $payload = [
            'username' => $validated['username'],
        ];

        if ($request->filled('password')) {
            $payload['password'] = $validated['password'];
        }

        BcchCredential::query()->updateOrCreate(
            ['id' => $credential?->id ?? 1],
            $payload,
        );

        return redirect()
            ->route('settings.bcch')
            ->with('success_bcch', 'Credenciales del Banco Central actualizadas correctamente.');
    }

    public function syncToday(EconomicIndicatorSyncService $syncService): RedirectResponse
    {
        $result = $syncService->ensureTodayUfIsAvailable(force: true);

        $flashKey = in_array($result['status'], ['synced', 'synced_with_cached_ipc', 'already_available'], true)
            ? 'success_bcch'
            : 'error_bcch';

        return redirect()
            ->route('settings.bcch')
            ->with($flashKey, $result['message']);
    }
}
