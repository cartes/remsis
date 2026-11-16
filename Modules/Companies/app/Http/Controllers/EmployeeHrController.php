<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\MedicalLeave;
use Modules\Employees\Models\VacationRequest;
use Modules\Employees\Services\EmploymentContractService;
use Modules\Employees\Services\MedicalLeaveService;
use Modules\Employees\Services\VacationService;

/**
 * Recursos Humanos sobre la ficha del colaborador: anexos de contrato, vacaciones y licencias médicas.
 */
class EmployeeHrController extends Controller
{
    public function __construct(
        private readonly EmploymentContractService $contracts,
        private readonly VacationService $vacations,
        private readonly MedicalLeaveService $leaves,
    ) {}

    /**
     * Registra un anexo: nuevas condiciones con fecha de vigencia.
     */
    public function storeAmendment(Request $request, Company $company, Employee $employee): RedirectResponse
    {
        Gate::authorize('manageEmployees', $company);

        $input = collect($request->all())->map(fn ($value) => $value === '' ? null : $value)->all();
        $data = validator($input, [
            'effective_date' => 'required|date',
            'base_salary' => 'required|numeric|min:0',
            'position' => 'nullable|string|max:150',
            'contract_type' => 'required|in:indefinido,plazo_fijo,por_obra,honorarios',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'work_schedule_type' => 'required|in:full_time,part_time,art_22',
            'weekly_hours' => 'required|integer|min:1|max:45',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'change_reason' => 'required|string|max:500',
        ], [], [
            'effective_date' => 'fecha de vigencia',
            'base_salary' => 'sueldo base',
            'weekly_hours' => 'jornada semanal',
            'change_reason' => 'motivo del anexo',
        ])->validateWithBag('amendment');

        if (($data['cost_center_id'] ?? null) !== null) {
            abort_unless($company->costCenters()->whereKey($data['cost_center_id'])->exists(), 422);
        }

        try {
            $this->contracts->registerAmendment($employee, $data, $request->user());
        } catch (ValidationException $e) {
            return back()->with('active_tab', 'contratos')->withErrors($e->errors(), 'amendment')->withInput();
        }

        return back()->with('active_tab', 'contratos')->with('success', 'Anexo de contrato registrado.');
    }

    /**
     * La empresa registra vacaciones directamente: quedan aprobadas de inmediato.
     */
    public function storeVacation(Request $request, Company $company, Employee $employee): RedirectResponse
    {
        Gate::authorize('manageRequests', $company);

        $data = $request->validateWithBag('vacation', [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($employee, $data, $request) {
                $vacation = $this->vacations->request(
                    $employee, $data['start_date'], $data['end_date'], $data['notes'] ?? null, VacationRequest::ORIGIN_COMPANY
                );
                $this->vacations->approve($vacation, $request->user());
            });
        } catch (ValidationException $e) {
            return back()->with('active_tab', 'vacaciones')->withErrors($e->errors(), 'vacation')->withInput();
        }

        return back()->with('active_tab', 'vacaciones')->with('success', 'Vacaciones registradas.');
    }

    public function approveVacation(Request $request, Company $company, Employee $employee, VacationRequest $vacationRequest): RedirectResponse
    {
        Gate::authorize('manageRequests', $company);

        return $this->resolve(fn () => $this->vacations->approve($vacationRequest, $request->user()), 'Vacaciones aprobadas.');
    }

    public function rejectVacation(Request $request, Company $company, Employee $employee, VacationRequest $vacationRequest): RedirectResponse
    {
        Gate::authorize('manageRequests', $company);

        $data = $request->validateWithBag('vacation', ['rejection_reason' => 'required|string|max:500']);

        return $this->resolve(
            fn () => $this->vacations->reject($vacationRequest, $request->user(), $data['rejection_reason']),
            'Solicitud de vacaciones rechazada.'
        );
    }

    public function cancelVacation(Request $request, Company $company, Employee $employee, VacationRequest $vacationRequest): RedirectResponse
    {
        Gate::authorize('manageRequests', $company);

        return $this->resolve(fn () => $this->vacations->cancel($vacationRequest, $request->user()), 'Vacaciones anuladas.');
    }

    /**
     * Saldo de apertura y años reconocidos con otros empleadores (feriado progresivo).
     */
    public function updateVacationSettings(Request $request, Company $company, Employee $employee): RedirectResponse
    {
        Gate::authorize('manageEmployees', $company);

        $input = collect($request->all())->map(fn ($value) => $value === '' ? null : $value)->all();
        $data = validator($input, [
            'vacation_initial_balance' => 'nullable|numeric|min:-60|max:200|required_with:vacation_initial_balance_date',
            'vacation_initial_balance_date' => 'nullable|date|before_or_equal:today|required_with:vacation_initial_balance',
            'previous_employment_months' => 'nullable|integer|min:0|max:600',
        ], [], [
            'vacation_initial_balance' => 'saldo de apertura',
            'vacation_initial_balance_date' => 'fecha del saldo de apertura',
            'previous_employment_months' => 'meses con otros empleadores',
        ])->validateWithBag('vacation');

        $employee->update([
            'vacation_initial_balance' => $data['vacation_initial_balance'] ?? null,
            'vacation_initial_balance_date' => $data['vacation_initial_balance_date'] ?? null,
            'previous_employment_months' => (int) ($data['previous_employment_months'] ?? 0),
        ]);

        return back()->with('active_tab', 'vacaciones')->with('success', 'Configuración de vacaciones actualizada.');
    }

    /**
     * Registra una licencia médica: marca sus días en la asistencia y estima el subsidio.
     */
    public function storeMedicalLeave(Request $request, Company $company, Employee $employee): RedirectResponse
    {
        Gate::authorize('manageEmployees', $company);

        $data = $request->validateWithBag('leave', [
            'folio' => 'required|string|max:30',
            'type' => 'required|in:'.implode(',', array_keys(MedicalLeave::TYPE_LABELS)),
            'start_date' => 'required|date',
            'days' => 'required|integer|min:1|max:365',
            'is_continuation' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ], [], [
            'type' => 'tipo de licencia',
            'start_date' => 'fecha de inicio',
            'days' => 'días de reposo',
        ]);

        try {
            $this->leaves->register($employee, $data, $request->user());
        } catch (ValidationException $e) {
            return back()->with('active_tab', 'licencias')->withErrors($e->errors(), 'leave')->withInput();
        }

        return back()->with('active_tab', 'licencias')->with('success', 'Licencia médica registrada.');
    }

    public function resolveMedicalLeave(Request $request, Company $company, Employee $employee, MedicalLeave $medicalLeave): RedirectResponse
    {
        Gate::authorize('manageEmployees', $company);

        $input = collect($request->all())->map(fn ($value) => $value === '' ? null : $value)->all();
        $data = validator($input, [
            'status' => 'required|in:'.implode(',', MedicalLeave::RESOLUTION_STATUSES),
            'authorized_days' => 'nullable|integer|min:1|required_if:status,'.MedicalLeave::STATUS_REDUCED,
            'resolution_date' => 'nullable|date|before_or_equal:today',
            'resolution_notes' => 'nullable|string|max:500',
        ], [], [
            'status' => 'resolución',
            'authorized_days' => 'días autorizados',
            'resolution_date' => 'fecha de la resolución',
        ])->validateWithBag('leave');

        return $this->resolve(
            fn () => $this->leaves->resolve(
                $medicalLeave,
                $data['status'],
                isset($data['authorized_days']) ? (int) $data['authorized_days'] : null,
                $data['resolution_date'] ?? null,
                $data['resolution_notes'] ?? null,
                $request->user()
            ),
            'Resolución de la licencia registrada.',
            'licencias',
            'leave'
        );
    }

    public function refreshMedicalLeaveSubsidy(Request $request, Company $company, Employee $employee, MedicalLeave $medicalLeave): RedirectResponse
    {
        Gate::authorize('manageEmployees', $company);
        abort_if($medicalLeave->status === MedicalLeave::STATUS_CANCELLED, 422);

        $this->leaves->refreshSubsidy($medicalLeave);

        return back()->with('active_tab', 'licencias')->with('success', 'Subsidio estimado nuevamente con las liquidaciones disponibles.');
    }

    public function cancelMedicalLeave(Request $request, Company $company, Employee $employee, MedicalLeave $medicalLeave): RedirectResponse
    {
        Gate::authorize('manageEmployees', $company);

        return $this->resolve(
            fn () => $this->leaves->cancel($medicalLeave, $request->user()),
            'Licencia médica anulada.',
            'licencias',
            'leave'
        );
    }

    private function resolve(callable $action, string $message, string $tab = 'vacaciones', string $bag = 'vacation'): RedirectResponse
    {
        try {
            $action();
        } catch (ValidationException $e) {
            return back()->with('active_tab', $tab)->withErrors($e->errors(), $bag);
        }

        return back()->with('active_tab', $tab)->with('success', $message);
    }
}
