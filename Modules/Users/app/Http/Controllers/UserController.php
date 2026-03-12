<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Modules\Employees\Models\Employee;
use Modules\Companies\Models\Company;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $auth = $request->user();
        $isSuperAdmin = $auth->hasRole('super-admin');
        $companyFilter = $isSuperAdmin ? $request->company_id : $auth->company_id;

        $query = User::query()
            ->whereHas('roles', function($q) use ($isSuperAdmin) {
                $roles = ['admin', 'contador', 'recursos-humanos'];

                if ($isSuperAdmin) {
                    $roles[] = 'super-admin';
                }

                $q->whereIn('name', $roles);
            })
            ->when(! $isSuperAdmin && ! $auth->company_id, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($companyFilter, function($q) use ($companyFilter) {
                $q->whereHas('employee', function($eq) use ($companyFilter) {
                    $eq->where('company_id', $companyFilter);
                });
            })
            ->with(['roles:id,name', 'employee.company:id,name'])
            ->select('id', 'name', 'email', 'status');

        $users = $query->orderByDesc('id')->get();

        // Roles visibles/seleccionables en UI para gestión administrativa
        $visibleRoles = $isSuperAdmin
            ? ['super-admin', 'admin', 'contador', 'recursos-humanos']
            : ['admin', 'contador', 'recursos-humanos'];

        $roles = Role::whereIn('name', $visibleRoles)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Cargamos empresas para el filtro y para vincular
        $companies = Company::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->whereKey($auth->company_id))
            ->orderBy('razon_social')
            ->get(['id', 'razon_social as name']);
        $companyId = $companyFilter;

        return view('users::index', compact('users', 'roles', 'companies', 'companyId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $auth = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|string|exists:roles,name',
        ]);

        if (! $auth->hasRole('super-admin') && $validated['role'] === 'super-admin') {
            abort(403);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => true, // Asegurar que el usuario esté activo por defecto
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'Usuario creado exitosamente.',
            'user' => $user->fresh(['roles:id,name']), // Recargar con roles
        ]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('users::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('users::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $auth = $request->user();
        $user = User::findOrFail($id);
        $this->authorizeManagedUser($auth, $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'nullable|string|exists:roles,name',
            'password' => 'nullable|string|min:6',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (!empty($validated['role'])) {
            if (! $auth->hasRole('super-admin') && $validated['role'] === 'super-admin') {
                abort(403);
            }

            $user->syncRoles([$validated['role']]);
        }

        if (isset($validated['company_id'])) {
            $this->authorizeCompanyAssignment($auth, (int) $validated['company_id']);

            if ($validated['company_id']) {
                \Modules\Employees\Models\Employee::updateOrCreate(
                    ['user_id' => $user->id],
                    ['company_id' => $validated['company_id']]
                );
            } else {
                // Si viene nulo/vacio, desvincular (opcional)
                \Modules\Employees\Models\Employee::where('user_id', $user->id)->delete();
            }
        }

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'user' => $user->load(['roles', 'employee.company']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorizeManagedUser(request()->user(), $user);

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    public function showJson($id)
    {
        $user = User::with('roles')->findOrFail($id);
        $this->authorizeManagedUser(request()->user(), $user);
        return response()->json($user);
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeManagedUser(request()->user(), $user);
        $user->status = !$user->status;
        $user->save();

        return response()->json([
            'message' => 'Estado del usuario actualizado correctamente.',
            'status' => $user->status,
        ]);
    }

    /**
     * Attach a company to a user.
     */
    public function attachCompany(Request $request, User $user)
    {
        $auth = $request->user();
        $this->authorizeManagedUser($auth, $user);

        // Opcional: restringe a roles que pueden tener empresa
        if (!$user->hasAnyRole(['admin', 'employee', 'contador', 'recursos-humanos'])) {
            return response()->json(['message' => 'Este usuario no admite vínculo a empresa.'], 403);
        }

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $this->authorizeCompanyAssignment($auth, (int) $validated['company_id']);

        // 🔒 Upsert por user_id: si existe, actualiza; si no, crea
        $employee = Employee::updateOrCreate(
            ['user_id' => $user->id],              // unique key
            ['company_id' => (int) $validated['company_id']]
        );

        $employee->load('company:id,name');

        return response()->json([
            'ok' => true,
            'company' => [
                'id' => $employee->company->id,
                'name' => $employee->company->name,
            ],
        ]);
    }

    protected function authorizeManagedUser(User $auth, User $target): void
    {
        if ($auth->hasRole('super-admin')) {
            return;
        }

        if ($target->hasRole('super-admin')) {
            abort(403);
        }

        $targetCompanyId = $target->employee?->company_id ?? $target->company_id;

        if ((int) $targetCompanyId !== (int) $auth->company_id) {
            abort(403);
        }
    }

    protected function authorizeCompanyAssignment(User $auth, ?int $companyId): void
    {
        if ($companyId === null || $auth->hasRole('super-admin')) {
            return;
        }

        if ((int) $companyId !== (int) $auth->company_id) {
            abort(403);
        }
    }
}
