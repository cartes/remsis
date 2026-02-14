<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Companies\Models\Company;
use Modules\Payroll\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollPeriodController extends Controller
{
    /**
     * Display a listing of payroll periods.
     */
    public function index(Request $request, Company $company)
    {
        $selectedYear = $request->get('year', now()->year);

        // Get periods for this company
        $periods = PayrollPeriod::with(['company'])
            ->where('company_id', $company->id)
            ->byYear($selectedYear)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(15);
        
        // Get available years for filter
        $availableYears = PayrollPeriod::where('company_id', $company->id)
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('payroll::periods.index', compact('periods', 'company', 'selectedYear', 'availableYears'));
    }

    /**
     * Show the form for creating a new period.
     */
    public function create(Company $company)
    {
        // Calculate default values
        $now = now();
        $defaultYear = $now->year;
        $defaultMonth = $now->month;
        
        // Calculate period dates (first and last day of month)
        $startDate = Carbon::create($defaultYear, $defaultMonth, 1)->format('Y-m-d');
        $endDate = Carbon::create($defaultYear, $defaultMonth, 1)->endOfMonth()->format('Y-m-d');
        
        // Suggest payment date (last day of month)
        $paymentDate = $endDate;

        return view('payroll::periods.create', compact(
            'company',
            'defaultYear',
            'defaultMonth',
            'startDate',
            'endDate',
            'paymentDate'
        ));
    }

    /**
     * Store a newly created period.
     */
    public function store(Request $request, Company $company)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2050',
            'month' => 'required|integer|min:1|max:12',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check for duplicate period
        $exists = PayrollPeriod::where('company_id', $company->id)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['month' => 'Ya existe un período para este mes y año.'])
                ->withInput();
        }

        try {
            $period = PayrollPeriod::create([
                'company_id' => $company->id,
                'year' => $validated['year'],
                'month' => $validated['month'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'],
                'status' => PayrollPeriod::STATUS_DRAFT,
            ]);

            return redirect()
                ->route('companies.payroll-periods.index', ['company' => $company])
                ->with('success', 'Período de nómina creado exitosamente: ' . $period->getDisplayName());
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al crear el período: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show the wizard for managing payroll lines.
     */
    public function wizard(Request $request, Company $company, $periodId)
    {
        $period = PayrollPeriod::where('company_id', $company->id)->findOrFail($periodId);
        
        // Get lines with employee data
        $lines = \Modules\Payroll\Models\PayrollLine::with(['employee.costCenter'])
            ->where('payroll_period_id', $period->id)
            ->get();
            
        // Filter by Cost Center if requested
        if ($request->has('cost_center_id') && $request->cost_center_id) {
            $lines = $lines->filter(function ($line) use ($request) {
                return $line->employee->cost_center_id == $request->cost_center_id;
            });
        }
        
        // Load cost centers for filter
        $costCenters = \Modules\Companies\Models\CostCenter::where('company_id', $company->id)->get();

        return view('payroll::periods.wizard', compact('company', 'period', 'lines', 'costCenters'));
    }

    /**
     * Calculate payroll for the period.
     */
    public function calculate(Request $request, Company $company, $periodId, \Modules\Payroll\Services\PayrollCalculationService $service)
    {
        $period = PayrollPeriod::where('company_id', $company->id)->findOrFail($periodId);
        
        try {
            $count = $service->calculatePeriod($period);
            
            return redirect()
                ->route('companies.payroll-periods.wizard', ['company' => $company, 'period' => $period->id])
                ->with('success', "Cálculo realizado exitosamente para $count empleados.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al calcular la nómina: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the status of a period.
     */
    public function updateStatus(Request $request, Company $company, $id)
    {
        $period = PayrollPeriod::findOrFail($id);
        $user = auth()->user();

        $validated = $request->validate([
            'status' => 'required|in:draft,open,closed,paid,calculated', // Added calculated
        ]);

        $newStatus = $validated['status'];
        $currentStatus = $period->status;

        // Define allowed transitions
        // We can allow transitions from calculated to closed too
        $allowedTransitions = [
            PayrollPeriod::STATUS_DRAFT => [PayrollPeriod::STATUS_OPEN, 'calculated'],
            PayrollPeriod::STATUS_OPEN => [PayrollPeriod::STATUS_CLOSED, 'calculated'],
            'calculated' => [PayrollPeriod::STATUS_CLOSED, PayrollPeriod::STATUS_OPEN],
            PayrollPeriod::STATUS_CLOSED => [PayrollPeriod::STATUS_PAID],
        ];
        
        // ... rest of the method logic
        
        // Check if transition is allowed
        $isAllowed = isset($allowedTransitions[$currentStatus]) && 
                     in_array($newStatus, $allowedTransitions[$currentStatus]);

        // Super-admin can reopen periods
        if (!$isAllowed && $user->hasRole('super-admin')) {
            $isAllowed = true;
        }

        if (!$isAllowed) {
            return response()->json([
                'success' => false,
                'message' => 'Transición de estado no permitida.',
            ], 422);
        }

        try {
            $period->status = $newStatus;
            $period->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente.',
                'status' => $period->status,
                'statusLabel' => $period->getStatusLabel(),
                'statusBadgeClass' => $period->getStatusBadgeClass(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Update a specific payroll line and recalculate.
     */
    public function updateLine(Request $request, Company $company, $periodId, $lineId, \Modules\Payroll\Services\PayrollCalculationService $service)
    {
        $period = PayrollPeriod::where('company_id', $company->id)->findOrFail($periodId);
        $line = \Modules\Payroll\Models\PayrollLine::where('payroll_period_id', $period->id)->findOrFail($lineId);

        $validated = $request->validate([
            'overtime_hours' => 'nullable|numeric|min:0',
            'anticipos_amount' => 'nullable|numeric|min:0',
            'otros_descuentos' => 'nullable|numeric|min:0',
            'gratification_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            // Update line with manual inputs
            if ($request->has('overtime_hours')) {
                $line->overtime_hours = $request->overtime_hours;
            }
            if ($request->has('anticipos_amount')) {
                $line->anticipos_amount = $request->anticipos_amount;
            }
            if ($request->has('otros_descuentos')) {
                $line->otros_descuentos = $request->otros_descuentos;
            }
            if ($request->has('gratification_amount')) {
                $line->gratification_amount = $request->gratification_amount;
            }
            $line->save();

            // Recalculate this employee's line to update totals
            // The service uses existing line data for overtime hours (and now gratification) if present
            $service->calculateEmployee($line->employee, $period);

            return response()->json([
                'success' => true,
                'message' => 'Línea de nómina actualizada y recalculada.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la línea: ' . $e->getMessage(),
            ], 500);
        }
    }
}
