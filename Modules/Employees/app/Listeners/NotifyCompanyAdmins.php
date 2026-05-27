<?php

declare(strict_types=1);

namespace Modules\Employees\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Employees\Events\AdministrativeDayRequested;
use Modules\Users\Models\User;

class NotifyCompanyAdmins
{
    /**
     * Handle the event.
     */
    public function handle(AdministrativeDayRequested $event): void
    {
        $request = $event->request;
        $employee = $request->employee;
        $company = $request->company;

        if (!$employee || !$company) {
            return;
        }

        // Retrieve company users who have roles: admin, recursos-humanos, contador.
        $admins = User::where(function ($q) use ($company) {
                $q->where('company_id', $company->id)
                  ->orWhereHas('companies', function ($query) use ($company) {
                      $query->where('companies.id', $company->id);
                  });
            })
            ->role(['admin', 'recursos-humanos', 'contador'])
            ->get();

        // Also notify super-admins
        $superAdmins = User::role('super-admin')->get();
        $notifiableUsers = $admins->merge($superAdmins)->unique('id');

        foreach ($notifiableUsers as $admin) {
            Log::info(sprintf(
                '[Notificación Día Administrativo] Para el Administrador %s (%s): El colaborador %s ha solicitado el día administrativo %s. Notas: %s',
                $admin->name,
                $admin->email,
                $employee->full_name,
                $request->requested_date->format('d/m/Y'),
                $request->notes ?? '(sin notas)'
            ));
        }
    }
}
