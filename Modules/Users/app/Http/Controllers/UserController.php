<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $auth = $request->user();
        $isSuperAdmin = $auth->hasRole('super-admin');
        $authorizedCompanyIds = $isSuperAdmin
            ? []
            : $auth->getAllCompanies()
                ->filter(fn (Company $c): bool => $auth->canAccessCompany($c))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

        $companyFilter = $request->company_id ? (int) $request->company_id : null;
        if (! $isSuperAdmin && $companyFilter !== null && ! in_array($companyFilter, $authorizedCompanyIds, true)) {
            abort(403);
        }

        $query = User::query()
            ->whereHas('roles', function ($q) use ($isSuperAdmin) {
                $roles = ['admin', 'contador', 'recursos-humanos', 'multi-company'];

                if ($isSuperAdmin) {
                    $roles[] = 'super-admin';
                }

                $q->whereIn('name', $roles);
            })
            ->when(! $isSuperAdmin && empty($authorizedCompanyIds), fn ($q) => $q->whereRaw('1 = 0'))
            ->when(! $isSuperAdmin && ! empty($authorizedCompanyIds), function ($q) use ($authorizedCompanyIds) {
                $q->where(function ($sub) use ($authorizedCompanyIds) {
                    $sub->whereIn('company_id', $authorizedCompanyIds)
                        ->orWhereHas('employee', fn ($eq) => $eq->whereIn('company_id', $authorizedCompanyIds))
                        ->orWhereHas('companies', fn ($cq) => $cq->whereIn('companies.id', $authorizedCompanyIds));
                });
            })
            ->when($companyFilter, function ($q) use ($companyFilter) {
                $q->where(function ($sub) use ($companyFilter) {
                    $sub->where('company_id', $companyFilter)
                        ->orWhereHas('employee', fn ($eq) => $eq->where('company_id', $companyFilter))
                        ->orWhereHas('companies', fn ($cq) => $cq->where('companies.id', $companyFilter));
                });
            })
            ->with(['roles:id,name', 'employee.company:id,name', 'companies:id,razon_social'])
            ->select('id', 'name', 'email', 'status', 'company_id', 'tenant_id');

        $users = $query->orderByDesc('id')->get();

        // Roles visibles/seleccionables en UI para gestión administrativa
        $visibleRoles = $isSuperAdmin
            ? ['super-admin', 'admin', 'contador', 'recursos-humanos', 'multi-company']
            : ['admin', 'contador', 'recursos-humanos'];

        $roles = Role::whereIn('name', $visibleRoles)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Cargamos empresas para el filtro y para vincular
        $companies = Company::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->whereIn('id', $authorizedCompanyIds))
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

        if (! $auth->hasRole('super-admin')) {
            $canManageAny = $auth->getAllCompanies()
                ->contains(fn (Company $c): bool => $auth->hasCompanyPermission($c, 'users.manage'));

            if (! $canManageAny) {
                abort(403);
            }
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
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'nullable|string|exists:roles,name',
            'password' => 'nullable|string|min:6',
            'company_id' => 'nullable|integer|exists:companies,id',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'integer|exists:companies,id',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (! empty($validated['role'])) {
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
                \Modules\Employees\Models\Employee::where('user_id', $user->id)->delete();
            }
        }

        if (isset($validated['company_ids'])) {
            foreach ($validated['company_ids'] as $cid) {
                $this->authorizeCompanyAssignment($auth, (int) $cid);
            }

            if ($auth->hasRole('super-admin')) {
                $user->companies()->sync($validated['company_ids']);
            } else {
                $user->companies()->syncWithoutDetaching($validated['company_ids']);
            }
        }

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'user' => $user->load(['roles', 'employee.company', 'companies']),
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
        $user->status = ! $user->status;
        $user->save();

        return response()->json([
            'message' => 'Estado del usuario actualizado correctamente.',
            'status' => $user->status,
        ]);
    }

    /**
     * Attach a company to a user (supports internal employees and external/outsourcing delegation).
     */
    public function attachCompany(Request $request, User $user)
    {
        $auth = $request->user();
        $this->authorizeManagedUser($auth, $user);

        if (! $user->hasAnyRole(['admin', 'employee', 'contador', 'recursos-humanos', 'multi-company'])) {
            return response()->json(['message' => 'Este usuario no admite vínculo a empresa.'], 403);
        }

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'role' => ['nullable', 'string', 'in:admin,contador,recursos-humanos'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'is_external' => ['nullable', 'boolean'],
        ]);

        $companyId = (int) $validated['company_id'];
        $this->authorizeCompanyAssignment($auth, $companyId);

        $company = Company::query()->withoutGlobalScopes()->findOrFail($companyId);
        $isExternal = $request->has('is_external')
            ? $request->boolean('is_external')
            : ($user->hasRole('multi-company') || ($user->hasRole('contador') && ! $user->employee()->exists()));

        $user->companies()->syncWithoutDetaching([
            $company->id => [
                'role' => $validated['role'] ?? $user->roleInCompany($company),
                'permissions' => isset($validated['permissions'])
                    ? json_encode(array_values($validated['permissions']))
                    : null,
                'is_external' => $isExternal,
                'delegated_by' => $auth->id,
            ],
        ]);

        // Solo creamos/actualizamos Employee si no es una delegación externa de outsourcing
        // y el usuario no tiene ya un vínculo laboral en otra empresa distinta.
        $existingEmployee = $user->employee()->withoutGlobalScopes()->first();
        if (! $isExternal && (! $existingEmployee || (int) $existingEmployee->company_id === $company->id)) {
            Employee::updateOrCreate(
                ['user_id' => $user->id],
                ['company_id' => $company->id]
            );
        }

        return response()->json([
            'ok' => true,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
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

        $authCompanyIds = $auth->getAllCompanies()
            ->filter(fn (Company $c): bool => $auth->hasCompanyPermission($c, 'users.manage'))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (empty($authCompanyIds)) {
            abort(403);
        }

        $targetCompanyIds = $target->getAllCompanies()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        // Si el usuario destino ya pertenece a una o más empresas, el actor debe administrar al menos una de ellas
        if (! empty($targetCompanyIds) && empty(array_intersect($authCompanyIds, $targetCompanyIds))) {
            abort(403);
        }
    }

    protected function authorizeCompanyAssignment(User $auth, ?int $companyId): void
    {
        if ($companyId === null || $auth->hasRole('super-admin')) {
            return;
        }

        if (! $auth->hasCompanyPermission($companyId, 'users.manage')) {
            abort(403);
        }
    }
}
