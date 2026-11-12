<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\AdministrativeDayRequest;
use Modules\Employees\Services\AdministrativeDayAttendanceService;

class CompanyRequestController extends Controller
{
    protected AdministrativeDayAttendanceService $attendanceService;

    public function __construct(AdministrativeDayAttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Approve a pending administrative day request.
     */
    public function approve(Request $request, Company $company, AdministrativeDayRequest $dayRequest)
    {
        $user = auth()->user();

        // Authorize access
        if (!$user->canAccessCompany($company)) {
            abort(403);
        }

        if ($user->hasRole('employee')) {
            abort(403, 'No autorizado.');
        }

        // Verify request belongs to the company
        if ($dayRequest->company_id !== $company->id) {
            abort(404);
        }

        try {
            $this->attendanceService->approveRequest($dayRequest, $user);
            return redirect()->back()->with('success', 'Solicitud aprobada correctamente.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /**
     * Reject a pending administrative day request.
     */
    public function reject(Request $request, Company $company, AdministrativeDayRequest $dayRequest)
    {
        $user = auth()->user();

        // Authorize access
        if (!$user->canAccessCompany($company)) {
            abort(403);
        }

        if ($user->hasRole('employee')) {
            abort(403, 'No autorizado.');
        }

        // Verify request belongs to the company
        if ($dayRequest->company_id !== $company->id) {
            abort(404);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $this->attendanceService->rejectRequest($dayRequest, $user, $request->input('rejection_reason'));
            return redirect()->back()->with('success', 'Solicitud rechazada correctamente.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }
}
