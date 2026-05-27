<?php

declare(strict_types=1);

namespace Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\AdministrativeDayRequest;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Services\AdministrativeDayBalanceService;
use Modules\Employees\Events\AdministrativeDayRequested;
use Modules\Payroll\Models\PayrollPeriod;

class EmployeeRequestController extends Controller
{
    protected AdministrativeDayBalanceService $balanceService;

    public function __construct(AdministrativeDayBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Store a newly created administrative day request.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'No tienes un perfil de colaborador asociado a tu usuario.');
        }

        $request->validate([
            'requested_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ]);

        $date = $request->input('requested_date');

        // Check duplicate request on the same date (unique constraint in DB)
        $exists = AdministrativeDayRequest::where('employee_id', $employee->id)
            ->where('requested_date', $date)
            ->first();

        if ($exists) {
            $statusText = match($exists->status) {
                'pending' => 'pendiente',
                'approved' => 'aprobada',
                'rejected' => 'rechazada',
                'cancelled' => 'cancelada',
                default => $exists->status
            };
            return redirect()->back()->withErrors([
                'requested_date' => ["Ya existe una solicitud en estado {$statusText} para esta fecha."],
            ]);
        }

        // Check if there is already an attendance record for this date
        $attendanceExists = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        if ($attendanceExists) {
            return redirect()->back()->withErrors([
                'requested_date' => ['Ya existe un registro de asistencia para esta fecha.'],
            ]);
        }

        // Check if payroll period is locked/closed
        $isPeriodLocked = PayrollPeriod::where('company_id', $employee->company_id)
            ->whereIn('status', [PayrollPeriod::STATUS_CLOSED, PayrollPeriod::STATUS_PAID])
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($isPeriodLocked) {
            return redirect()->back()->withErrors([
                'requested_date' => ['No se pueden solicitar días administrativos en períodos de nómina cerrados o pagados.'],
            ]);
        }

        // Check balance
        $balance = $this->balanceService->getOrCreateBalance($employee, $date);
        if (!$balance) {
            return redirect()->back()->withErrors([
                'requested_date' => ['La política de días administrativos no está activa para esta empresa.'],
            ]);
        }

        // Check pending requests in the same cycle
        $pendingCount = AdministrativeDayRequest::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->whereBetween('requested_date', [$balance->cycle_starts_on, $balance->cycle_ends_on])
            ->count();

        if (($balance->available_units - $pendingCount) < 1) {
            return redirect()->back()->withErrors([
                'requested_date' => ['No tienes suficientes días administrativos disponibles en este ciclo (considerando solicitudes pendientes).'],
            ]);
        }

        // Create the request
        $dayRequest = AdministrativeDayRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'requested_date' => $date,
            'status' => 'pending',
            'notes' => $request->input('notes'),
        ]);

        // Dispatch notification event
        event(new AdministrativeDayRequested($dayRequest));

        return redirect()->back()->with('success', 'Solicitud de día administrativo ingresada correctamente.');
    }

    /**
     * Cancel a pending administrative day request.
     */
    public function cancel(Request $request, AdministrativeDayRequest $dayRequest)
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee || $dayRequest->employee_id !== $employee->id) {
            abort(403, 'No autorizado.');
        }

        if ($dayRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Solo se pueden cancelar solicitudes en estado pendiente.');
        }

        // Check if payroll period is locked/closed
        $date = $dayRequest->requested_date->format('Y-m-d');
        $isPeriodLocked = PayrollPeriod::where('company_id', $employee->company_id)
            ->whereIn('status', [PayrollPeriod::STATUS_CLOSED, PayrollPeriod::STATUS_PAID])
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($isPeriodLocked) {
            return redirect()->back()->with('error', 'No se pueden cancelar solicitudes en períodos de nómina cerrados o pagados.');
        }

        $dayRequest->update([
            'status' => 'cancelled',
        ]);

        return redirect()->back()->with('success', 'Solicitud cancelada correctamente.');
    }
}
