<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CostCenter;
use Modules\Payroll\Exceptions\PayrollException;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollDetail;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Services\PayrollCalculationService;

class PayrollPeriodController extends Controller
{
    /**
     * Display a listing of payroll periods.
     */
    public function index(Request $request, Company $company)
    {
        Gate::authorize('viewPayroll', $company);

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
        Gate::authorize('calculatePayroll', $company);

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
        Gate::authorize('calculatePayroll', $company);

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
                ->with('success', 'Período de nómina creado exitosamente: '.$period->getDisplayName());
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al crear el período: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show the wizard for managing payroll lines.
     */
    public function wizard(Request $request, Company $company, PayrollPeriod $period)
    {
        Gate::authorize('viewPayroll', $company);

        // Get lines with employee data
        $lines = Payroll::with(['employee.costCenter', 'details'])
            ->where('payroll_period_id', $period->id)
            ->get();

        // Filter by Cost Center if requested
        if ($request->has('cost_center_id') && $request->cost_center_id) {
            $lines = $lines->filter(function ($line) use ($request) {
                return $line->employee->cost_center_id == $request->cost_center_id;
            });
        }

        // Load cost centers for filter
        $costCenters = CostCenter::where('company_id', $company->id)->get();

        // Load enabled taxable items
        $enabledTaxables = $company->taxableItems()->wherePivot('is_enabled', true)->get();

        return view('payroll::periods.wizard', compact('company', 'period', 'lines', 'costCenters', 'enabledTaxables'));
    }

    /**
     * Calculate payroll for the period.
     */
    public function calculate(Request $request, Company $company, PayrollPeriod $period, PayrollCalculationService $service)
    {
        Gate::authorize('calculatePayroll', $company);

        try {
            $count = $service->calculatePeriod($period);

            return redirect()
                ->route('companies.payroll-periods.wizard', ['company' => $company, 'period' => $period->id])
                ->with('success', "Cálculo realizado exitosamente para $count empleados.");
        } catch (PayrollException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al calcular la nómina: '.$e->getMessage()]);
        }
    }

    /**
     * Update the status of a period.
     */
    public function updateStatus(Request $request, Company $company, PayrollPeriod $period)
    {
        Gate::authorize('approvePayroll', $company);
        $user = auth()->user();

        $validated = $request->validate([
            'status' => 'required|in:draft,open,closed,paid,calculated',
        ]);

        $newStatus = $validated['status'];

        $allowedTransitions = [
            PayrollPeriod::STATUS_DRAFT => [PayrollPeriod::STATUS_OPEN, PayrollPeriod::STATUS_CALCULATED],
            PayrollPeriod::STATUS_OPEN => [PayrollPeriod::STATUS_CLOSED, PayrollPeriod::STATUS_CALCULATED],
            PayrollPeriod::STATUS_CALCULATED => [PayrollPeriod::STATUS_CLOSED, PayrollPeriod::STATUS_OPEN],
            PayrollPeriod::STATUS_CLOSED => [PayrollPeriod::STATUS_PAID],
        ];

        try {
            // La fila se bloquea para que el cierre y un cálculo simultáneo se ejecuten uno tras otro.
            $isAllowed = DB::transaction(function () use ($period, $newStatus, $allowedTransitions, $user) {
                $locked = PayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();

                // Sólo el super-admin puede saltarse el flujo, por ejemplo para reabrir un período cerrado.
                $isAllowed = in_array($newStatus, $allowedTransitions[$locked->status] ?? [], true)
                    || $user->hasRole('super-admin');

                if ($isAllowed) {
                    $locked->status = $newStatus;
                    $locked->save();
                    $period->setRawAttributes($locked->getAttributes(), true);
                }

                return $isAllowed;
            });

            if (! $isAllowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transición de estado no permitida.',
                ], 422);
            }

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
                'message' => 'Error al actualizar el estado: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a specific payroll line and recalculate.
     */
    public function updateLine(Request $request, Company $company, PayrollPeriod $period, Payroll $payroll, PayrollCalculationService $service)
    {
        Gate::authorize('calculatePayroll', $company);

        $line = $payroll;

        $validated = $request->validate([
            'overtime_hours' => 'nullable|numeric|min:0',
            'anticipos_amount' => 'nullable|numeric|min:0',
            'otros_descuentos' => 'nullable|numeric|min:0',
            'gratification_amount' => 'nullable|numeric|min:0',
            'details' => 'nullable|array',
            'details.*.type' => 'required|string',
            'details.*.description' => 'required|string',
            'details.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request, $period, $line, $service) {
                // El período se bloquea antes de escribir: una línea de un período cerrado no se edita.
                $service->lockEditablePeriod($period);

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

                // Sync Multi-Detail Variable Taxables
                $taxableCodes = PayrollCalculationService::MANUAL_DETAIL_CONCEPTS;
                PayrollDetail::where('payroll_id', $line->id)
                    ->whereIn('concept', $taxableCodes)
                    ->delete();

                $sumCO01 = 0;
                $sumSC01 = 0;
                $sumBI01 = 0;
                $sumAG01 = 0;

                if ($request->has('details') && is_array($request->details)) {
                    foreach ($request->details as $detail) {
                        if (in_array($detail['type'], $taxableCodes)) {
                            PayrollDetail::create([
                                'payroll_id' => $line->id,
                                'type' => 'earning',
                                'concept' => $detail['type'],
                                'description' => $detail['description'],
                                'amount' => $detail['amount'],
                            ]);

                            if ($detail['type'] === 'CO01') {
                                $sumCO01 += $detail['amount'];
                            }
                            if ($detail['type'] === 'SC01') {
                                $sumSC01 += $detail['amount'];
                            }
                            if ($detail['type'] === 'BI01') {
                                $sumBI01 += $detail['amount'];
                            }
                            if ($detail['type'] === 'AG01') {
                                $sumAG01 += $detail['amount'];
                            }
                        }
                    }
                }

                $line->comisiones_amount = $sumCO01;
                $line->semana_corrida_amount = $sumSC01;
                $line->bonos_imponibles_amount = $sumBI01;
                $line->aguinaldos_amount = $sumAG01;

                $line->save();

                // Recalculate this employee's line to update totals
                $service->calculateEmployee($line->employee, $period);
            });

            // Re-load the updated line with details for the frontend
            $updatedLine = Payroll::with(['employee.costCenter', 'details'])->findOrFail($line->id);

            return response()->json([
                'success' => true,
                'message' => 'Línea de nómina actualizada y recalculada.',
                'payroll' => $updatedLine,
            ]);

        } catch (PayrollException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la línea: '.$e->getMessage(),
            ], 500);
        }
    }
}
