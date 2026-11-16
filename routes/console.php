<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Un anexo de contrato con vigencia futura se refleja en la ficha del colaborador el día que
| empieza a regir. La nómina no depende de esto: siempre lee la versión vigente en el mes liquidado.
*/
Artisan::command('remsis:apply-contract-versions', function () {
    $context = app(\App\Support\Tenancy\TenantContext::class);
    $service = app(\Modules\Employees\Services\EmploymentContractService::class);
    $context->bypass();

    try {
        $updated = 0;
        \Modules\Employees\Models\Employee::query()
            ->whereIn('id', \Modules\Employees\Models\EmploymentContract::query()
                ->where('version_type', \Modules\Employees\Models\EmploymentContract::VERSION_AMENDMENT)
                ->inForceOn(now())
                ->select('employee_id'))
            ->chunkById(200, function ($employees) use ($service, &$updated) {
                foreach ($employees as $employee) {
                    $updated += (int) $service->applyVersionInForce($employee);
                }
            });
    } finally {
        $context->bypass(false);
    }

    $this->info("Fichas actualizadas: {$updated}");
})->purpose('Aplica a las fichas los anexos de contrato que entraron en vigencia');

Schedule::command('remsis:apply-contract-versions')->dailyAt('00:30');
