<?php

namespace Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('employees::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employees::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show()
    {
        $user = auth()->user();

        if (! $user || ! $user->hasPersonalPortalAccess()) {
            abort(403, 'No tienes un vínculo laboral activo para acceder al portal personal.');
        }

        $employee = $user->employee()
            ->withoutGlobalScopes()
            ->with(['company', 'afp', 'isapre', 'bank', 'ccaf', 'costCenter'])
            ->first();

        $balance = null;
        $requests = collect();

        if ($employee) {
            $balanceService = app(\Modules\Employees\Services\AdministrativeDayBalanceService::class);
            $balance = $balanceService->getOrCreateBalance($employee, now());

            $requests = \Modules\Employees\Models\AdministrativeDayRequest::where('employee_id', $employee->id)
                ->with('resolver')
                ->orderBy('requested_date', 'desc')
                ->get();
        }

        return view('employees::profile.show', compact('user', 'employee', 'balance', 'requests'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('employees::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
