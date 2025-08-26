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
        $companyId = $request->query('company_id');

        $users = User::query()
            ->with([
                'employee.company:id,name',
                'roles:id,name',
            ])
            ->when($companyId, function ($q) use ($companyId) {
                $q->whereHas('employee', fn($qq) => $qq->where('company_id', $companyId));
            })
            ->select('id', 'name', 'email', 'status') // incluye lo que uses en la tabla
            ->orderByDesc('id')
            ->get();

        $roles = Role::select('name')->get();

        $companies = Company::orderBy('name')->get(['id', 'name']);


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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'Usuario creado exitosamente.',
            'user' => $user->load('roles'),
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
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'nullable|string|exists:roles,name',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        if ($validated['role']) {
            $user->syncRoles([$validated['role']]);
        }

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    public function showJson($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json($user);
    }

    public function toggleStatus(User $user)
    {
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
        // Opcional: restringe a roles que pueden tener empresa
        if (!$user->hasAnyRole(['admin', 'employee', 'contador'])) {
            return response()->json(['message' => 'Este usuario no admite vínculo a empresa.'], 403);
        }

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

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
}
