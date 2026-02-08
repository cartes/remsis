<?php

namespace Modules\Companies\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Users\Models\User;
use Modules\Employees\Models\Employee;
use Modules\Companies\Models\Company;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CompanyEmployeeController extends Controller
{
    public function store(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        // 1. Crear el usuario
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => true,
        ]);

        // 2. Asignar rol employee
        $user->assignRole('employee');

        // 3. Vincular a la empresa vía el modelo Employee
        Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Empleado creado y vinculado correctamente.',
            'employee' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
            ]
        ]);
    }

    public function destroy(Company $company, User $user)
    {
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
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Empleado desvinculado de la empresa.'
        ]);
    }

    public function getPayroll(Company $company, Employee $employee)
    {
        // Asegurar que el empleado pertenece a la empresa
        if ($employee->company_id !== $company->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $employee->load('user');

        return response()->json([
            'status' => 'success',
            'employee' => $employee
        ]);
    }

    public function updatePayroll(Request $request, Company $company, Employee $employee)
    {
        if ($employee->company_id !== $company->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'rut' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'position' => 'nullable|string',
            'afp_id' => 'nullable|exists:afps,id',
            'isapre_id' => 'nullable|exists:isapres,id',
            'ccaf_id' => 'nullable|exists:ccafs,id',
            'salary' => 'nullable|numeric',
            'salary_type' => 'nullable|in:mensual,quincenal,semanal',
            'contract_type' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'bank_id' => 'nullable|exists:banks,id',
            'bank_account_number' => 'nullable|string',
            'bank_account_type' => 'nullable|in:corriente,vista,ahorro',
        ]);

        $employee->update($validated);

        // También actualizar el nombre en el usuario si cambió
        $user = $employee->user;
        if ($user) {
            $user->update([
                'name' => "{$validated['first_name']} {$validated['last_name']}",
                'email' => $validated['email'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Datos de nómina actualizados correctamente.',
            'employee' => $employee->fresh('user')
        ]);
    }
}
