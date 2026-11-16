<?php

namespace Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Employees\Models\AdministrativeDayRequest;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeDocument;
use Modules\Employees\Models\VacationRequest;
use Modules\Employees\Services\VacationService;
use Modules\Employees\Services\AdministrativeDayBalanceService;
use Modules\Payroll\Models\EmployeeItem;
use Modules\Payroll\Models\Item;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Services\PayslipService;

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
        $payslips = collect();
        $itemsByTab = ['haberes' => collect(), 'descuentos' => collect(), 'creditos' => collect()];
        $documents = collect();
        $vacationBalance = null;
        $vacations = collect();

        if ($employee) {
            $balanceService = app(AdministrativeDayBalanceService::class);
            $balance = $balanceService->getOrCreateBalance($employee, now());

            $requests = AdministrativeDayRequest::where('employee_id', $employee->id)
                ->with('resolver')
                ->orderBy('requested_date', 'desc')
                ->get();

            // El colaborador sólo ve liquidaciones de períodos cerrados o pagados.
            $payslips = $this->ownPayslips($employee)
                ->with(['period' => fn ($period) => $period->withoutGlobalScopes()])
                ->orderByDesc('period_year')
                ->orderByDesc('period_month')
                ->get();

            $items = EmployeeItem::withoutGlobalScopes()
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->with(['item' => fn ($item) => $item->withoutGlobalScopes()])
                ->get()
                ->filter(fn ($assignment) => $assignment->item !== null);

            $itemsByTab = [
                'haberes' => $items->filter(fn ($a) => in_array($a->item->type, [Item::TYPE_HABER_IMPONIBLE, Item::TYPE_HABER_NO_IMPONIBLE], true))->values(),
                'descuentos' => $items->filter(fn ($a) => in_array($a->item->type, [Item::TYPE_DESCUENTO_LEGAL, Item::TYPE_DESCUENTO_VARIOS], true))->values(),
                'creditos' => $items->filter(fn ($a) => $a->item->type === Item::TYPE_CREDITO)->values(),
            ];

            $vacationBalance = app(VacationService::class)->balance($employee);
            $vacations = VacationRequest::withoutGlobalScopes()
                ->where('employee_id', $employee->id)
                ->orderByDesc('start_date')
                ->get();

            $documents = EmployeeDocument::withoutGlobalScopes()
                ->where('employee_id', $employee->id)
                ->latest()
                ->get();
        }

        return view('employees::profile.show', compact(
            'user', 'employee', 'balance', 'requests', 'payslips', 'itemsByTab', 'documents', 'vacationBalance', 'vacations'
        ));
    }

    /**
     * Liquidación de sueldo propia en PDF.
     */
    public function payslip(Request $request, int $payroll, PayslipService $payslips)
    {
        $employee = $this->currentEmployee();
        $line = $this->ownPayslips($employee)->findOrFail($payroll);

        $pdf = $payslips->pdf($line);
        $filename = $payslips->filename($line);

        return $request->boolean('inline') ? $pdf->stream($filename) : $pdf->download($filename);
    }

    /**
     * Descarga de un documento de la carpeta digital propia.
     */
    public function document(int $document)
    {
        $employee = $this->currentEmployee();
        $file = EmployeeDocument::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->findOrFail($document);

        abort_unless(Storage::disk('local')->exists($file->file_path), 404, 'Archivo no encontrado.');

        return Storage::disk('local')->download($file->file_path, $file->title.'.pdf');
    }

    private function currentEmployee(): Employee
    {
        $employee = auth()->user()?->employee()->withoutGlobalScopes()->first();

        abort_if($employee === null, 403, 'No tienes un vínculo laboral activo para acceder al portal personal.');

        return $employee;
    }

    /**
     * Liquidaciones emitidas del propio colaborador. El filtro por colaborador reemplaza al de tenant,
     * porque el portal personal no opera dentro del contexto de una empresa administrada.
     */
    private function ownPayslips(Employee $employee)
    {
        return Payroll::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->issued();
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
