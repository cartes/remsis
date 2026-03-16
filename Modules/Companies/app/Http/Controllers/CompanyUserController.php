<?php

namespace Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;

class CompanyUserController extends Controller
{
    /**
     * Display a listing of the company users (Admin, HR, Accounting).
     */
    public function index(Company $company, Request $request)
    {
        $auth = $request->user();
        if (! $auth->hasRole('super-admin') && $auth->company_id !== $company->id) {
            abort(403);
        }

        // We want all users who are linked to this company AND have admin, accountant, or HR roles.
        // Except super-admins, unless we want to show them? No.
        $users = User::whereHas('employee', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'contador', 'recursos-humanos']);
        })->with('roles')->get();

        $roles = Role::whereIn('name', ['admin', 'contador', 'recursos-humanos'])->get();

        return view('companies::users.index', compact('company', 'users', 'roles'));
    }

    /**
     * Store a new user for the company (or the initial Company Admin).
     */
    public function store(Request $request, Company $company)
    {
        $auth = $request->user();
        if (! $auth->hasRole('super-admin') && $auth->company_id !== $company->id) {
            // A super-admin can create users for any company.
            // But if a company admin is creating, they must belong to this company.
            abort(403);
        }

        // Normalizar booleanos desde strings (FormData)
        if ($request->has('is_in_payroll')) {
            $val = $request->input('is_in_payroll');
            if ($val === 'true') $request->merge(['is_in_payroll' => true]);
            if ($val === 'false') $request->merge(['is_in_payroll' => false]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => ['required', Rule::in(['admin', 'contador', 'recursos-humanos'])],
            'is_in_payroll' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => true,
        ]);

        $user->assignRole($validated['role']);

        $isInPayroll = $request->boolean('is_in_payroll', false);

        Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'is_in_payroll' => $isInPayroll,
            'first_name' => explode(' ', $validated['name'])[0] ?? '',
            'last_name' => explode(' ', $validated['name'])[1] ?? '',
            'status' => 'active',
            'email' => $validated['email'],
            'contract_type' => 'indefinido',
        ]);

        return redirect()->back()->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Update user details and role.
     */
    public function update(Request $request, Company $company, User $user)
    {
        $auth = $request->user();
        if (! $auth->hasRole('super-admin') && $auth->company_id !== $company->id) {
            abort(403);
        }

        // Normalizar booleanos desde strings (FormData)
        if ($request->has('is_in_payroll')) {
            $val = $request->input('is_in_payroll');
            if ($val === 'true') $request->merge(['is_in_payroll' => true]);
            if ($val === 'false') $request->merge(['is_in_payroll' => false]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|min:6',
            'role' => ['required', Rule::in(['admin', 'contador', 'recursos-humanos'])],
            'is_in_payroll' => 'boolean',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $user->syncRoles([$validated['role']]);

        $isInPayroll = $request->boolean('is_in_payroll', false);

        Employee::updateOrCreate(
            ['user_id' => $user->id, 'company_id' => $company->id],
            [
                'is_in_payroll' => $isInPayroll,
                'first_name' => explode(' ', $validated['name'])[0] ?? '',
                'last_name' => explode(' ', $validated['name'])[1] ?? '',
                'email' => $validated['email'],
            ]
        );

        return redirect()->back()->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Delete user from company
     */
    public function destroy(Company $company, User $user, Request $request)
    {
        $auth = $request->user();
        if (! $auth->hasRole('super-admin') && $auth->company_id !== $company->id) {
            abort(403);
        }

        // Just unassign from company? Or delete user completely?
        // Let's delete user or unassign.
        $user->delete();

        return redirect()->back()->with('success', 'Usuario eliminado correctamente.');
    }
}
