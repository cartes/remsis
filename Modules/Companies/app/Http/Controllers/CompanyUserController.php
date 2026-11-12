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
     * Display a listing of the company users (Admin, HR, Accounting, and delegated external operators).
     */
    public function index(Company $company, Request $request)
    {
        $auth = $request->user();
        if (! $auth->canAccessCompany($company)) {
            abort(403);
        }

        $users = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->where(function ($query) use ($company) {
                $query->whereHas('companies', function ($q) use ($company) {
                    $q->where('companies.id', $company->id);
                })->orWhere(function ($sub) use ($company) {
                    $sub->where(function ($empOrDirect) use ($company) {
                        $empOrDirect->where('company_id', $company->id)
                            ->orWhereHas('employee', fn ($eq) => $eq->where('company_id', $company->id));
                    })->whereHas('roles', function ($rq) {
                        $rq->whereIn('name', ['admin', 'contador', 'recursos-humanos', 'multi-company']);
                    });
                });
            })
            ->with(['roles', 'companies' => fn ($q) => $q->where('companies.id', $company->id)])
            ->get();

        $roles = Role::whereIn('name', ['admin', 'contador', 'recursos-humanos'])->get();

        return view('companies::users.index', compact('company', 'users', 'roles'));
    }

    /**
     * Store a new user or delegate company access to an existing external user.
     */
    public function store(Request $request, Company $company)
    {
        $auth = $request->user();
        if (! $auth->hasCompanyPermission($company, 'users.manage')) {
            abort(403);
        }

        // Normalizar booleanos desde strings (FormData)
        if ($request->has('is_in_payroll')) {
            $val = $request->input('is_in_payroll');
            if ($val === 'true') {
                $request->merge(['is_in_payroll' => true]);
            }
            if ($val === 'false') {
                $request->merge(['is_in_payroll' => false]);
            }
        }

        $existingUser = $request->filled('email')
            ? User::where('email', (string) $request->input('email'))->first()
            : null;

        if ($existingUser && $existingUser->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => [$existingUser ? 'nullable' : 'required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => [$existingUser ? 'nullable' : 'required', 'min:6'],
            'role' => ['required', Rule::in(['admin', 'contador', 'recursos-humanos'])],
            'is_in_payroll' => ['boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $isInPayroll = $request->boolean('is_in_payroll', false);

        if ($existingUser) {
            $user = $existingUser;
            if (! $user->hasAnyRole(User::ADMIN_ROLES)) {
                $user->assignRole($validated['role']);
            }
        } else {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => true,
                'company_id' => $isInPayroll ? $company->id : null,
            ]);

            $user->assignRole($validated['role']);
        }

        // Registrar membresía contextual en company_user (ADR-002 / ADR-003)
        $user->companies()->syncWithoutDetaching([
            $company->id => [
                'role' => $validated['role'],
                'permissions' => isset($validated['permissions'])
                    ? json_encode(array_values($validated['permissions']))
                    : null,
                'is_external' => ! $isInPayroll,
                'delegated_by' => $auth->id,
            ],
        ]);

        // Solo creamos vínculo laboral (Employee) si realmente pertenece a la nómina de la empresa.
        // Los contadores externos y operadores de outsourcing no deben tener un Employee ficticio.
        if ($isInPayroll) {
            $fullName = $validated['name'] ?? $user->name;
            Employee::updateOrCreate(
                ['user_id' => $user->id, 'company_id' => $company->id],
                [
                    'is_in_payroll' => true,
                    'first_name' => explode(' ', $fullName)[0] ?? '',
                    'last_name' => explode(' ', $fullName)[1] ?? '',
                    'status' => 'active',
                    'email' => $user->email,
                    'contract_type' => 'indefinido',
                ]
            );
        }

        return redirect()->back()->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Update user details, contextual role and delegated permissions in the company.
     */
    public function update(Request $request, Company $company, User $user)
    {
        $auth = $request->user();
        if (! $auth->hasCompanyPermission($company, 'users.manage')) {
            abort(403);
        }

        if ($user->hasRole('super-admin') || ! $user->belongsToCompany($company)) {
            abort(403);
        }

        // Normalizar booleanos desde strings (FormData)
        if ($request->has('is_in_payroll')) {
            $val = $request->input('is_in_payroll');
            if ($val === 'true') {
                $request->merge(['is_in_payroll' => true]);
            }
            if ($val === 'false') {
                $request->merge(['is_in_payroll' => false]);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|min:6',
            'role' => ['required', Rule::in(['admin', 'contador', 'recursos-humanos'])],
            'is_in_payroll' => 'boolean',
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Si el usuario solo opera en esta empresa, sincronizamos su rol base;
        // si es multi-tenant, preservamos sus otros roles y actualizamos el rol contextual en el pivote.
        if ($user->getAllCompanies()->count() <= 1) {
            $preserveEmployeeRole = $user->hasRole('employee');
            $rolesToSync = [$validated['role']];
            if ($preserveEmployeeRole) {
                $rolesToSync[] = 'employee';
            }
            $user->syncRoles($rolesToSync);
        } elseif (! $user->hasAnyRole(User::ADMIN_ROLES)) {
            $user->assignRole($validated['role']);
        }

        $isInPayroll = $request->boolean('is_in_payroll', false);

        $user->companies()->syncWithoutDetaching([
            $company->id => [
                'role' => $validated['role'],
                'permissions' => array_key_exists('permissions', $validated)
                    ? ($validated['permissions'] !== null ? json_encode(array_values($validated['permissions'])) : null)
                    : $user->companies()->where('companies.id', $company->id)->first()?->pivot?->permissions,
                'is_external' => ! $isInPayroll,
                'delegated_by' => $auth->id,
            ],
        ]);

        if ($isInPayroll || $user->employee()->where('company_id', $company->id)->exists()) {
            Employee::updateOrCreate(
                ['user_id' => $user->id, 'company_id' => $company->id],
                [
                    'is_in_payroll' => $isInPayroll,
                    'first_name' => explode(' ', $validated['name'])[0] ?? '',
                    'last_name' => explode(' ', $validated['name'])[1] ?? '',
                    'email' => $validated['email'],
                ]
            );
        }

        return redirect()->back()->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Revoke user membership from company (or delete user if exclusive to this company).
     */
    public function destroy(Company $company, User $user, Request $request)
    {
        $auth = $request->user();
        if (! $auth->hasCompanyPermission($company, 'users.manage')) {
            abort(403);
        }

        if ($user->hasRole('super-admin') || ! $user->belongsToCompany($company)) {
            abort(403);
        }

        $company->administrators()->detach($user->id);

        if ((int) $user->company_id === (int) $company->id) {
            $user->company_id = null;
            $user->save();
        }

        // Si el usuario administra o pertenece a otras empresas (p. ej. contador externo),
        // solo revocamos su acceso a este tenant sin eliminar su cuenta global.
        if ($user->fresh()->getAllCompanies()->isEmpty()) {
            $user->delete();
        }

        return redirect()->back()->with('success', 'Usuario eliminado correctamente.');
    }
}
