<?php

namespace Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\Isapre;
use Modules\Companies\Models\CostCenter;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeLog;

class EmployeesController extends Controller
{
    public function index()
    {
        return view('employees::index');
    }

    public function create()
    {
        return view('employees::create');
    }

    public function store(Request $request) {}

    public function show($id)
    {
        return redirect()->route('employees.edit', $id);
    }

    public function edit($id)
    {
        $employee = Employee::with([
            'afp', 'isapre', 'ccaf', 'bank', 'costCenter', 'company', 'user',
            'logs'      => fn ($q) => $q->latest(),
            'logs.user',
        ])->findOrFail($id);

        $afps        = Afp::orderBy('nombre')->get();
        $isapres     = Isapre::orderBy('nombre')->get();
        $costCenters = CostCenter::where('company_id', $employee->company_id)->active()->orderBy('name')->get();
        $fieldLabels = EmployeeLog::$fieldLabels;

        return view('employees::edit', compact('employee', 'afps', 'isapres', 'costCenters', 'fieldLabels'));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $section  = $request->input('section', 'personales');

        match ($section) {
            'personales'  => $this->updatePersonal($request, $employee),
            'previsional' => $this->updatePrevisional($request, $employee),
            'laboral'     => $this->updateLaboral($request, $employee),
            default       => null,
        };

        return redirect()
            ->route('employees.edit', $employee)
            ->with('success', 'Datos actualizados correctamente.')
            ->with('active_tab', $section);
    }

    public function destroy($id) {}

    // ──────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────

    private function updatePersonal(Request $request, Employee $employee): void
    {
        $data = $request->validate([
            'first_name'  => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'rut'         => ['required', 'string', 'max:20', Rule::unique('employees', 'rut')->ignore($employee->id)],
            'email'       => ['nullable', 'email', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_date'  => ['nullable', 'date', 'before:today'],
            'gender'      => ['nullable', Rule::in(['male', 'female', 'other'])],
        ], [], [
            'first_name' => 'nombres',
            'last_name'  => 'apellidos',
            'rut'        => 'RUT',
            'birth_date' => 'fecha de nacimiento',
            'gender'     => 'género',
        ]);

        $employee->update($data);
    }

    private function updatePrevisional(Request $request, Employee $employee): void
    {
        $data = $request->validate([
            'health_system'       => ['nullable', Rule::in(['fonasa', 'isapre'])],
            'isapre_id'           => ['nullable', 'exists:isapres,id'],
            'health_contribution' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'afp_id'              => ['nullable', 'exists:afps,id'],
            'contract_type'       => ['nullable', Rule::in(['indefinido', 'plazo_fijo', 'obra_faena', 'honorarios'])],
            'apv_amount'          => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ], [], [
            'health_system'       => 'sistema de salud',
            'isapre_id'           => 'Isapre',
            'health_contribution' => 'valor plan de salud',
            'afp_id'              => 'AFP',
            'contract_type'       => 'tipo de contrato',
            'apv_amount'          => 'monto APV',
        ]);

        if (($data['health_system'] ?? null) === 'fonasa') {
            $data['isapre_id']           = null;
            $data['health_contribution'] = null;
        }

        $employee->update($data);
    }

    private function updateLaboral(Request $request, Employee $employee): void
    {
        $data = $request->validate([
            'position'           => ['nullable', 'string', 'max:255'],
            'cost_center_id'     => ['nullable', 'exists:cost_centers,id'],
            'hire_date'          => ['nullable', 'date'],
            'salary'             => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'salary_type'        => ['nullable', Rule::in(['mensual', 'quincenal', 'semanal'])],
        ], [], [
            'position'      => 'cargo',
            'cost_center_id' => 'departamento/área',
            'hire_date'     => 'fecha de ingreso',
            'salary'        => 'sueldo base',
            'salary_type'   => 'tipo de remuneración',
        ]);

        $data['is_in_payroll'] = $request->boolean('is_in_payroll');

        $employee->update($data);
    }
}
