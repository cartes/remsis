<?php

declare(strict_types=1);

namespace Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\VacationRequest;
use Modules\Employees\Services\VacationService;

/**
 * Vacaciones desde el portal personal: el colaborador solicita y anula sus propias solicitudes pendientes.
 */
class EmployeeVacationController extends Controller
{
    public function __construct(private readonly VacationService $vacations) {}

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        $data = $request->validateWithBag('vacation', [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ], [], ['start_date' => 'fecha de inicio', 'end_date' => 'fecha de término']);

        try {
            $this->vacations->request(
                $employee, $data['start_date'], $data['end_date'], $data['notes'] ?? null, VacationRequest::ORIGIN_EMPLOYEE
            );
        } catch (ValidationException $e) {
            return back()->with('portal_tab', 'vacaciones')->withErrors($e->errors(), 'vacation')->withInput();
        }

        return back()->with('portal_tab', 'vacaciones')->with('success', 'Solicitud de vacaciones ingresada. Queda pendiente de aprobación.');
    }

    public function cancel(Request $request, int $vacationRequest): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $vacation = VacationRequest::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->findOrFail($vacationRequest);

        if ($vacation->status !== VacationRequest::STATUS_PENDING) {
            return back()->with('portal_tab', 'vacaciones')
                ->withErrors(['status' => 'Sólo puedes anular solicitudes pendientes. Para una ya aprobada, contacta a tu empresa.'], 'vacation');
        }

        $vacation->update([
            'status' => VacationRequest::STATUS_CANCELLED,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return back()->with('portal_tab', 'vacaciones')->with('success', 'Solicitud anulada.');
    }

    private function currentEmployee(): Employee
    {
        $employee = auth()->user()?->employee()->withoutGlobalScopes()->first();

        abort_if($employee === null, 403, 'No tienes un vínculo laboral activo para acceder al portal personal.');

        return $employee;
    }
}
