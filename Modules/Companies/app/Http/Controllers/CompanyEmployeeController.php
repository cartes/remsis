<?php

namespace Modules\Companies\Http\Controllers;

use App\Rules\Rut;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\EmployeeItem;
use Modules\Payroll\Models\Item;
use Modules\Users\Models\User;
use Throwable;

class CompanyEmployeeController extends Controller
{
    public function store(Request $request, Company $company)
    {
        $this->authorizeCompanyAccess($company);

        $validated = $request->validate([
            // Cuenta de acceso
            'first_name'          => 'required|string|max:255',
            'last_name'           => 'required|string|max:255',
            'email'               => 'required|email|unique:users,email',
            'password'            => 'required|min:6',
            // Personal
            'rut'                 => ['nullable', 'string', new Rut],
            'birth_date'          => 'nullable|date',
            'gender'              => 'nullable|string|max:50',
            'phone'               => 'nullable|string|max:30',
            'address'             => 'nullable|string|max:500',
            'nationality'         => 'nullable|string|max:100',
            // Laboral
            'position'            => 'nullable|string|max:255',
            'hire_date'           => 'nullable|date',
            'contract_type'       => 'nullable|string|max:50',
            'work_schedule_type'  => 'nullable|in:full_time,part_time',
            'cost_center_id'      => 'nullable|exists:cost_centers,id',
            // Previsión
            'afp_id'              => 'nullable|exists:afps,id',
            'health_system'       => 'nullable|in:fonasa,isapre',
            'isapre_id'           => 'nullable|exists:isapres,id',
            'health_contribution' => 'nullable|numeric|min:0',
            'ccaf_id'             => 'nullable|exists:ccafs,id',
            'apv_amount'          => 'nullable|numeric|min:0',
            // Remuneraciones
            'salary'              => 'nullable|numeric|min:0',
            'salary_type'         => 'nullable|in:mensual,quincenal,semanal',
            'num_dependents'      => 'nullable|integer|min:0',
            // Pago
            'payment_method'      => 'nullable|in:transferencia,cheque,efectivo',
            'bank_id'             => 'nullable|required_if:payment_method,transferencia|exists:banks,id',
            'bank_account_type'   => 'nullable|required_if:payment_method,transferencia|in:corriente,vista,ahorro',
            'bank_account_number' => 'nullable|required_if:payment_method,transferencia|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            // 1. Crear usuario
            $user = User::create([
                'name'       => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email'      => $validated['email'],
                'password'   => Hash::make($validated['password']),
                'status'     => true,
            ]);

            $user->assignRole('employee');

            // 2. Crear registro de empleado con todos los campos del wizard
            $employee = Employee::create([
                'user_id'             => $user->id,
                'company_id'          => $company->id,
                'first_name'          => $validated['first_name'],
                'last_name'           => $validated['last_name'],
                'email'               => $validated['email'],
                'rut'                 => $validated['rut'] ?? null,
                'birth_date'          => $validated['birth_date'] ?? null,
                'gender'              => $validated['gender'] ?? null,
                'phone'               => $validated['phone'] ?? null,
                'address'             => $validated['address'] ?? null,
                'nationality'         => $validated['nationality'] ?? null,
                'position'            => $validated['position'] ?? null,
                'hire_date'           => $validated['hire_date'] ?? null,
                'contract_type'       => $validated['contract_type'] ?? null,
                'work_schedule_type'  => $validated['work_schedule_type'] ?? 'full_time',
                'cost_center_id'      => $validated['cost_center_id'] ?? null,
                'afp_id'              => $validated['afp_id'] ?? null,
                'health_system'       => $validated['health_system'] ?? 'fonasa',
                'isapre_id'           => $validated['isapre_id'] ?? null,
                'health_contribution' => $validated['health_contribution'] ?? null,
                'ccaf_id'             => $validated['ccaf_id'] ?? null,
                'apv_amount'          => $validated['apv_amount'] ?? null,
                'salary'              => $validated['salary'] ?? null,
                'salary_type'         => $validated['salary_type'] ?? 'mensual',
                'num_dependents'      => $validated['num_dependents'] ?? 0,
                'bank_id'             => $validated['bank_id'] ?? null,
                'bank_account_type'   => $validated['bank_account_type'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'payment_method'      => $validated['payment_method'] ?? 'efectivo',
                'is_in_payroll'       => true,
                'status'              => 'active',
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear colaborador: ' . $e->getMessage(), [
                'file'    => $e->getFile() . ':' . $e->getLine(),
                'company' => $company->id,
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Error al crear el colaborador. Por favor intenta nuevamente.',
            ], 500);
        }

        session()->flash('success', 'Colaborador creado y vinculado correctamente.');

        return response()->json([
            'status' => 'success',
            'message' => 'Colaborador creado y vinculado correctamente.',
            'employee' => [
                'id' => $employee->id, // Add this line
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
            ],
        ]);
    }

    public function destroy(Company $company, User $user)
    {
        $this->authorizeCompanyAccess($company);

        // En este sistema, "Nómina" gestiona al usuario como empleado.
        // Desvincular implica borrar el registro en 'employees'.
        // Si el usuario SOLO tiene el rol employee, podríamos borrar al usuario también,
        // pero para evitar pérdida de datos accidental, solo desvinculamos o borramos employee.

        $employee = Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        if ($employee) {
            $employee->delete();

            // Opcional: si queremos borrar al usuario físico si no tiene otros roles
            // $user->delete();
            session()->flash('success', 'Colaborador desvinculado de la empresa.');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Colaborador desvinculado de la empresa.',
        ]);
    }

    public function getPayroll(Company $company, Employee $employee)
    {
        $this->authorizeCompanyAccess($company);

        $employee->load(['user.roles']);

        return response()->json([
            'status' => 'success',
            'employee' => $employee,
        ]);
    }

    public function updatePayroll(Request $request, Company $company, Employee $employee)
    {
        $this->authorizeCompanyAccess($company);

        // Limpiar strings vacíos para que las reglas 'nullable' funcionen incluso si el middleware central no está activo
        $input = $request->all();
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $input[$key] = null;
                } elseif ($key === 'is_in_payroll') {
                    // Handle boolean strings from FormData
                    if ($trimmed === 'true') {
                        $input[$key] = true;
                    }
                    if ($trimmed === 'false') {
                        $input[$key] = false;
                    }
                }
            }
        }
        $request->merge($input);

        $validated = $request->validate([
            // Datos personales
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'rut' => ['nullable', 'string', new Rut],
            'email' => [
                'nullable',
                'email',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($employee->user_id),
            ],
            'phone' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:100',
            'marital_status' => 'nullable|in:single,married,divorced,widowed,other',
            'address' => 'nullable|string',

            // Datos laborales
            'position' => 'nullable|string',
            'hire_date' => 'nullable|date',
            'contract_type' => 'nullable|string',
            'work_schedule' => 'nullable|string',
            'work_schedule_type' => 'nullable|in:full_time,part_time',
            'part_time_hours' => 'nullable|numeric|min:0',
            'part_time_schedule' => 'nullable|json',
            'cost_center_id' => 'nullable|exists:cost_centers,id',

            // Previsión social
            'afp_id' => 'nullable|exists:afps,id',
            'isapre_id' => 'nullable|exists:isapres,id',
            'ccaf_id' => 'nullable|exists:ccafs,id',
            'health_contribution' => 'nullable|numeric|min:0',
            'apv_amount' => 'nullable|numeric|min:0',

            // Remuneración
            'salary' => 'nullable|numeric|min:0',
            'salary_type' => 'nullable|in:mensual,quincenal,semanal',
            'num_dependents' => 'nullable|integer|min:0',

            // Datos bancarios
            'bank_id' => 'nullable|exists:banks,id',
            'bank_account_number' => 'nullable|string',
            'bank_account_type' => 'nullable|in:corriente,vista,ahorro',

            // Contacto emergencia
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',

            // Estado
            'status' => 'nullable|in:active,inactive',
            'is_in_payroll' => 'nullable|boolean',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if (array_key_exists('is_in_payroll', $validated)) {
            $validated['is_in_payroll'] = filter_var($validated['is_in_payroll'], FILTER_VALIDATE_BOOLEAN);
        }

        $user = $employee->user()->firstOrFail();
        $newProfilePhotoPath = null;
        $previousProfilePhotoPath = $user->profile_photo;

        if ($request->hasFile('profile_photo')) {
            $newProfilePhotoPath = $request->file('profile_photo')->store(
                "companies/{$company->id}/profile-photos",
                'public'
            );
        }

        try {
            DB::transaction(function () use ($employee, $user, $validated, $newProfilePhotoPath) {
                $employeeData = $validated;
                if (isset($employeeData['part_time_schedule']) && is_string($employeeData['part_time_schedule'])) {
                    $employeeData['part_time_schedule'] = json_decode($employeeData['part_time_schedule'], true);
                }
                unset($employeeData['profile_photo']);

                $employee->update($employeeData);

                $changed = false;

                if (! empty($validated['first_name']) && ! empty($validated['last_name'])) {
                    $newName = trim("{$validated['first_name']} {$validated['last_name']}");

                    if ($user->name !== $newName) {
                        $user->name = $newName;
                        $changed = true;
                    }
                }

                if (! empty($validated['email']) && $user->email !== $validated['email']) {
                    $user->email = $validated['email'];
                    $changed = true;
                }

                if ($newProfilePhotoPath !== null) {
                    $user->profile_photo = $newProfilePhotoPath;
                    $changed = true;
                }

                if ($changed) {
                    $user->save();
                }
            });
        } catch (Throwable $exception) {
            if ($newProfilePhotoPath !== null && Storage::disk('public')->exists($newProfilePhotoPath)) {
                Storage::disk('public')->delete($newProfilePhotoPath);
            }

            throw $exception;
        }

        if (
            $newProfilePhotoPath !== null
            && ! empty($previousProfilePhotoPath)
            && $previousProfilePhotoPath !== $newProfilePhotoPath
            && Storage::disk('public')->exists($previousProfilePhotoPath)
        ) {
            Storage::disk('public')->delete($previousProfilePhotoPath);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Datos de nómina actualizados correctamente.',
            'employee' => $employee->fresh(['user.roles']),
        ]);
    }

    public function search(Request $request, Company $company)
    {
        $this->authorizeCompanyAccess($company);

        $term = $request->query('query');

        if (strlen($term) < 3) {
            return response()->json([]);
        }

        $employees = Employee::where('company_id', $company->id)
            ->whereHas('user', function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->with(['user:id,name,email,status,profile_photo', 'user.roles:id,name'])
            ->limit(10)
            ->get();

        return response()->json($employees);
    }

    private function authorizeCompanyAccess(Company $company): void
    {
        $user = auth()->user();

        if (! $user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }
    }

    // -------------------------------------------------------------------------
    // Ficha de edición del colaborador (admin)
    // -------------------------------------------------------------------------

    public function edit(Company $company, Employee $employee)
    {
        $this->authorizeCompanyAccess($company);

        $employee->load(['user', 'afp', 'isapre', 'bank', 'ccaf', 'costCenter', 'employeeItems.item']);

        $afps        = \Modules\AdminPanel\Models\Afp::orderBy('nombre')->get();
        $isapres     = \Modules\AdminPanel\Models\Isapre::orderBy('nombre')->get();
        $ccafs       = \Modules\AdminPanel\Models\Ccaf::orderBy('nombre')->get();
        $bancos      = \Modules\AdminPanel\Models\Bank::orderBy('name')->get();
        $costCenters = \Modules\Companies\Models\CostCenter::where('company_id', $company->id)->get();
        $catalogItems = Item::forCompany($company->id)->orderBy('name')->get();

        return view('companies::employees.edit', compact(
            'company', 'employee', 'afps', 'isapres', 'ccafs', 'bancos', 'costCenters', 'catalogItems'
        ));
    }

    public function update(Request $request, Company $company, Employee $employee)
    {
        $this->authorizeCompanyAccess($company);

        $section = $request->input('section', 'personal');

        $rules = match ($section) {
            'personal' => [
                'first_name'  => 'required|string|max:100',
                'last_name'   => 'required|string|max:100',
                'rut'         => ['nullable', 'string', 'max:12', Rule::unique('employees', 'rut')->ignore($employee->id)],
                'email'       => ['nullable', 'email', Rule::unique('employees', 'email')->ignore($employee->id)],
                'birth_date'  => 'nullable|date',
                'gender'      => 'nullable|in:male,female,other',
                'nationality' => 'nullable|string|max:100',
                'phone'       => 'nullable|string|max:20',
                'address'     => 'nullable|string|max:255',
            ],
            'laboral' => [
                'position'           => 'nullable|string|max:150',
                'hire_date'          => 'nullable|date',
                'contract_type'      => 'nullable|string|max:50',
                'work_schedule_type' => 'nullable|in:full_time,part_time',
                'part_time_hours'    => 'nullable|numeric|min:1|max:40',
                'cost_center_id'     => 'nullable|exists:cost_centers,id',
                'is_in_payroll'      => 'boolean',
            ],
            'previsional' => [
                'afp_id'              => 'nullable|exists:afps,id',
                'health_system'       => 'nullable|in:fonasa,isapre',
                'isapre_id'           => 'nullable|exists:isapres,id',
                'health_contribution' => 'nullable|numeric|min:0',
                'ccaf_id'             => 'nullable|exists:ccafs,id',
                'apv_amount'          => 'nullable|numeric|min:0',
            ],
            'remuneraciones' => [
                'salary'              => 'nullable|numeric|min:0',
                'salary_type'         => 'nullable|in:mensual,quincenal,semanal',
                'payment_method'      => 'nullable|in:transferencia,cheque,efectivo',
                'bank_id'             => 'nullable|required_if:payment_method,transferencia|exists:banks,id',
                'bank_account_type'   => 'nullable|required_if:payment_method,transferencia|in:corriente,vista,ahorro',
                'bank_account_number' => 'nullable|required_if:payment_method,transferencia|string|max:50',
            ],
            default => [],
        };

        $validated = $request->validate($rules);
        $employee->update($validated);

        if ($section === 'personal' && isset($validated['first_name'])) {
            $employee->user?->update([
                'name'  => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'] ?? $employee->user->email,
            ]);
        }

        return back()->with('success', 'Información actualizada correctamente.');
    }

    // -------------------------------------------------------------------------
    // CRUD de employee_items
    // -------------------------------------------------------------------------

    public function storeItem(Request $request, Company $company, Employee $employee)
    {
        $this->authorizeCompanyAccess($company);

        $validated = $request->validate([
            'item_id'            => 'required|exists:items,id',
            'amount'             => 'required|numeric|min:0',
            'unit'               => 'required|in:CLP,UF,UTM,PERCENTAGE',
            'periodicity'        => 'required|in:fixed,variable',
            'total_installments' => 'nullable|integer|min:1',
            'notes'              => 'nullable|string|max:500',
        ]);

        // Verificar que el item pertenece a esta empresa
        $item = Item::where('id', $validated['item_id'])
            ->where('company_id', $company->id)
            ->firstOrFail();

        $employeeItem = EmployeeItem::create([
            'employee_id'        => $employee->id,
            'item_id'            => $item->id,
            'amount'             => $validated['amount'],
            'unit'               => $validated['unit'],
            'periodicity'        => $validated['periodicity'],
            'total_installments' => $validated['total_installments'] ?? null,
            'current_installment' => $item->type === Item::TYPE_CREDITO ? 0 : null,
            'is_active'          => true,
            'notes'              => $validated['notes'] ?? null,
        ]);

        return response()->json(['success' => true, 'item' => $employeeItem->load('item')]);
    }

    public function updateItem(Request $request, Company $company, Employee $employee, EmployeeItem $employeeItem)
    {
        $this->authorizeCompanyAccess($company);

        $validated = $request->validate([
            'amount'             => 'sometimes|numeric|min:0',
            'unit'               => 'sometimes|in:CLP,UF,UTM,PERCENTAGE',
            'periodicity'        => 'sometimes|in:fixed,variable',
            'total_installments' => 'nullable|integer|min:1',
            'is_active'          => 'sometimes|boolean',
            'notes'              => 'nullable|string|max:500',
        ]);

        $employeeItem->update($validated);

        return response()->json(['success' => true, 'item' => $employeeItem->load('item')]);
    }

    public function destroyItem(Company $company, Employee $employee, EmployeeItem $employeeItem)
    {
        $this->authorizeCompanyAccess($company);

        $employeeItem->delete();

        return response()->json(['success' => true]);
    }
}
