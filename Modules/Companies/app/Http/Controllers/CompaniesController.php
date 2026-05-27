<?php

namespace Modules\Companies\Http\Controllers;

use App\Rules\Rut;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Companies\Models\Company;

class CompaniesController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Si no es super-admin, filtramos las empresas a las que tiene acceso.
        $query = Company::orderBy('razon_social')->orderBy('name');

        if (! $user->hasRole('super-admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('id', $user->company_id)
                    ->orWhereHas('administrators', function ($sq) use ($user) {
                        $sq->where('users.id', $user->id);
                    });
            });
        }

        $companies = $query->get();

        // REGLA: Si no es super-admin y solo tiene una empresa, redirigir al dashboard directamente.
        if (! $user->hasRole('super-admin') && $companies->count() === 1) {
            $company = $companies->first();
            session(['selected_company_id' => $company->id]);

            return redirect()->route('companies.dashboard', $company);
        }

        return view('companies::index', compact('companies'));
    }

    public function create()
    {
        if (! auth()->user()->hasRole('super-admin')) {
            abort(403, 'No tiene permiso para crear empresas.');
        }

        return view('companies::create');
    }

    public function store(Request $request)
    {
        if (! auth()->user()->hasRole('super-admin')) {
            abort(403);
        }
        $validated = $request->validate(
            [
                'razon_social' => ['required', 'string', 'max:255'],
                'rut' => ['required', 'string', 'max:20', 'unique:companies,rut', new Rut],
            ],
            [
                'razon_social.required' => 'La razón social es obligatoria.',
                'rut.required' => 'El RUT es obligatorio.',
                'rut.unique' => 'El RUT ya está registrado en otra empresa.',
            ]
        );

        $data = $validated + ['name' => $validated['razon_social']]; // por NOT NULL en name
        $company = Company::create($data);

        session()->flash('success', 'Empresa creada. Completa la ficha debajo.');

        // Siempre JSON (como pediste):
        return response()->json([
            'status' => 'success',
            'message' => 'Empresa creada. Completa la ficha debajo.',
            'id' => $company->id,
        ], 201);
    }

    public function edit(Company $company)
    {
        $user = auth()->user();
        if (! $user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }
        $company->load(['economicActivity', 'administrativeDayPolicy']);

        $isPolicyLocked = false;
        if ($company->administrativeDayPolicy && $company->administrativeDayPolicy->is_enabled) {
            $resolver = app(\Modules\Employees\Services\AdministrativeDayCycleResolver::class);
            $resolved = $resolver->resolve(now(), $company->administrativeDayPolicy->cycle_type);
            $isPolicyLocked = \Modules\Employees\Models\AdministrativeDayUsage::where('company_id', $company->id)
                ->whereHas('balance', function ($q) use ($resolved) {
                    $q->where('cycle_key', $resolved['cycle_key']);
                })
                ->exists();
        }

        $ccafs = \Modules\AdminPanel\Models\Ccaf::orderBy('nombre')->get(['id', 'nombre']);
        $bancos = \Modules\AdminPanel\Models\Bank::orderBy('name')->get(['id', 'name as nombre']);
        $afps = \Modules\AdminPanel\Models\Afp::orderBy('nombre')->get(['id', 'nombre']);
        $isapres = \Modules\AdminPanel\Models\Isapre::orderBy('nombre')->get(['id', 'nombre']);
        $mutuales = \Modules\AdminPanel\Models\Mutual::orderBy('nombre')->get(['id', 'nombre']);

        // Cargar empleados de esta empresa
        $employees = \Modules\Employees\Models\Employee::where('company_id', $company->id)
            ->with(['user:id,name,email,status,profile_photo', 'user.roles:id,name'])
            ->get();

        // Cargar centros de costo
        $costCenters = $company->costCenters()->orderBy('code')->get();

        $taxableItems = collect();
        $enabledTaxableItemIds = [];
        if ($user->hasAnyRole(['super-admin', 'admin', 'contador'])) {
            $taxableItems = \Modules\AdminPanel\Models\TaxableItem::where('is_active', true)->get();
            $enabledTaxableItemIds = $company->taxableItems()->wherePivot('is_enabled', true)->pluck('taxable_items_catalog.id')->toArray();
        }

        // Cargar solicitudes de días administrativos de la empresa
        $dayRequests = \Modules\Employees\Models\AdministrativeDayRequest::where('company_id', $company->id)
            ->with(['employee', 'resolver'])
            ->orderBy('requested_date', 'desc')
            ->get();

        return view('companies::edit', compact('company', 'ccafs', 'bancos', 'afps', 'isapres', 'employees', 'costCenters', 'mutuales', 'taxableItems', 'enabledTaxableItemIds', 'isPolicyLocked', 'dayRequests'));
    }

    public function employees(Company $company)
    {
        $user = auth()->user();
        if (! $user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }

        $ccafs = \Modules\AdminPanel\Models\Ccaf::orderBy('nombre')->get(['id', 'nombre']);
        $bancos = \Modules\AdminPanel\Models\Bank::orderBy('name')->get(['id', 'name as nombre']);
        $afps = \Modules\AdminPanel\Models\Afp::orderBy('nombre')->get(['id', 'nombre']);
        $isapres = \Modules\AdminPanel\Models\Isapre::orderBy('nombre')->get(['id', 'nombre']);

        // Cargar empleados de esta empresa
        $employees = \Modules\Employees\Models\Employee::where('company_id', $company->id)
            ->with(['user:id,name,email,status,profile_photo', 'user.roles:id,name'])
            ->get();

        // Cargar centros de costo activos
        $costCenters = $company->costCenters()->active()->orderBy('code')->get(['id', 'code', 'name']);

        // Contar administradores de la empresa (rol 'admin')
        $adminCount = \Modules\Users\Models\User::whereHas('employee', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->role('admin')->count();

        return view('companies::employees', compact('company', 'ccafs', 'bancos', 'afps', 'isapres', 'employees', 'costCenters', 'adminCount'));
    }

    public function update(Request $request, Company $company)
    {
        $user = auth()->user();
        if (! $user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }

        if ($request->has('work_schedule') && is_string($request->work_schedule)) {
            $request->merge(['work_schedule' => json_decode($request->work_schedule, true)]);
        }
        $data = $request->validate([
            'nombre_fantasia' => ['nullable', 'string', 'max:255'],
            'giro' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'name' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'comuna' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'tipo_contribuyente' => ['nullable', Rule::in(['natural', 'juridica'])],
            'dia_pago' => ['nullable', Rule::in(['ultimo_dia_habil', 'dia_fijo', 'quincenal'])],
            'dia_pago_dia' => ['nullable', 'integer', 'min:1', 'max:31', 'required_if:dia_pago,dia_fijo'],
            'payment_frequency' => ['required', Rule::in(['diario', 'semanal', 'mensual'])],
            'ccaf_id' => ['nullable', 'exists:ccafs,id'],
            'mutual_id' => ['nullable', 'exists:mutuales,id'],
            'bank_id' => ['nullable', 'exists:banks,id'],
            'cuenta_bancaria' => ['nullable', 'string', 'max:100'],
            'gratification_system' => ['required', Rule::in(['sin_gratificacion', 'art_47', 'art_50', 'convencional'])],
            'gratification_months' => ['required', 'integer', 'min:1', 'max:12'],
            'representante_nombre' => ['nullable', 'string', 'max:255'],
            'representante_rut' => ['nullable', 'string', 'max:20', new Rut],
            'representante_cargo' => ['nullable', 'string', 'max:100'],
            'representante_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'weekly_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'work_schedule' => ['nullable', 'array'],
            'allows_overtime' => ['nullable', 'boolean'],
            'economic_activity_id' => ['nullable', 'exists:economic_activities,id'],
        ], [
            'dia_pago_dia.required_if' => 'Debes indicar el día cuando el pago es “Día fijo”.',
        ]);

        // Validar política de días administrativos si se intenta habilitar
        $policyRules = [];
        if ($request->boolean('administrative_day_policy_enabled')) {
            $policyRules = [
                'administrative_day_policy_days' => 'required|numeric|min:0.5|max:365',
                'administrative_day_policy_unit' => 'required|in:full_day,half_day',
                'administrative_day_policy_cycle' => 'required|in:annual,semiannual',
            ];
        }

        if (!empty($policyRules)) {
            $request->validate($policyRules, [
                'administrative_day_policy_days.required' => 'La cantidad de días administrativos es obligatoria.',
                'administrative_day_policy_days.numeric' => 'La cantidad de días administrativos debe ser numérica.',
                'administrative_day_policy_days.min' => 'La cantidad de días administrativos debe ser al menos 0.5.',
                'administrative_day_policy_unit.required' => 'La unidad de la política es obligatoria.',
                'administrative_day_policy_cycle.required' => 'El tipo de ciclo es obligatorio.',
            ]);
        }

        // Verificar si la política está bloqueada por consumos existentes
        $existingPolicy = $company->administrativeDayPolicy;
        $isLocked = false;
        if ($existingPolicy && $existingPolicy->is_enabled) {
            $resolver = app(\Modules\Employees\Services\AdministrativeDayCycleResolver::class);
            $resolved = $resolver->resolve(now(), $existingPolicy->cycle_type);
            $isLocked = \Modules\Employees\Models\AdministrativeDayUsage::where('company_id', $company->id)
                ->whereHas('balance', function ($q) use ($resolved) {
                    $q->where('cycle_key', $resolved['cycle_key']);
                })
                ->exists();
        }

        if ($isLocked) {
            if ($request->has('administrative_day_policy_days') && (float) $request->input('administrative_day_policy_days') !== (float) $existingPolicy->days_per_cycle) {
                return back()->withErrors(['administrative_day_policy_days' => 'No se puede modificar la cantidad de días porque ya existen consumos en el ciclo actual.']);
            }
            if ($request->has('administrative_day_policy_unit') && $request->input('administrative_day_policy_unit') !== $existingPolicy->unit) {
                return back()->withErrors(['administrative_day_policy_unit' => 'No se puede modificar la unidad porque ya existen consumos en el ciclo actual.']);
            }
            if ($request->has('administrative_day_policy_cycle') && $request->input('administrative_day_policy_cycle') !== $existingPolicy->cycle_type) {
                return back()->withErrors(['administrative_day_policy_cycle' => 'No se puede modificar el tipo de ciclo porque ya existen consumos en el ciclo actual.']);
            }
        }

        $company->update($data);

        // Guardar o actualizar la política de días administrativos
        $isEnabled = $request->boolean('administrative_day_policy_enabled');
        $policy = $company->administrativeDayPolicy()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'is_enabled' => $isEnabled,
                'days_per_cycle' => $isEnabled ? (float) $request->input('administrative_day_policy_days') : ($existingPolicy->days_per_cycle ?? 0.0),
                'unit' => $isEnabled ? $request->input('administrative_day_policy_unit') : ($existingPolicy->unit ?? 'full_day'),
                'cycle_type' => $isEnabled ? $request->input('administrative_day_policy_cycle') : ($existingPolicy->cycle_type ?? 'annual'),
            ]
        );

        if ($isEnabled) {
            app(\Modules\Employees\Services\AdministrativeDayProvisioner::class)->provisionCompany($company);
        }

        if ($user->hasAnyRole(['super-admin', 'admin', 'contador']) && $request->has('taxable_items_config')) {
            $taxableItemsInput = $request->input('taxable_items', []);
            $syncData = [];
            foreach ($taxableItemsInput as $id) {
                $syncData[$id] = ['is_enabled' => true];
            }
            $company->taxableItems()->sync($syncData);
        }

        $params = ['company' => $company];
        if ($request->has('section')) {
            $params['section'] = $request->input('section');
        }
        if ($request->has('tab')) {
            $params['tab'] = $request->input('tab');
        }

        return redirect()
            ->route('companies.edit', $params)
            ->with('success', 'Ficha de empresa guardada.');
    }

    public function editEssentials(Company $company)
    {
        return view('companies::essentials_edit', compact('company'));
    }

    public function updateEssentials(Request $request, Company $company)
    {
        $data = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'max:20', Rule::unique('companies', 'rut')->ignore($company->id), new Rut],
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'rut.required' => 'El RUT es obligatorio.',
            'rut.unique' => 'El RUT ya está registrado en otra empresa.',
        ]);

        // Si usas NOT NULL en 'name', sincroniza si quieres mantenerlo igual a razón social:
        if (empty($company->name)) {
            $data['name'] = $data['razon_social'];
        }

        $company->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Datos esenciales actualizados.',
            ]);
        }

        return redirect()
            ->route('companies.edit', $company)
            ->with('success', 'Datos esenciales actualizados.');
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Empresa eliminada correctamente.');
    }

    /** Reglas compartidas */
    protected function rules(?int $ignoreId = null): array
    {
        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'nombre_fantasia' => ['nullable', 'string', 'max:255'],
            'rut' => ['nullable', 'string', 'max:20', Rule::unique('companies', 'rut')->ignore($ignoreId), new Rut],
            'giro' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'comuna' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'tipo_contribuyente' => ['nullable', Rule::in(['natural', 'juridica'])],
            'dia_pago' => ['nullable', Rule::in(['ultimo_dia_habil', 'dia_fijo', 'quincenal'])],
            'dia_pago_dia' => ['nullable', 'integer', 'min:1', 'max:31', 'required_if:dia_pago,dia_fijo'],

            // FKs catálogo
            'ccaf_id' => ['nullable', 'exists:ccafs,id'],
            'bank_id' => ['nullable', 'exists:banks,id'],

            // cuenta si los mantienes
            'cuenta_bancaria' => ['nullable', 'string', 'max:100'],

            // Representante
            'representante_nombre' => ['nullable', 'string', 'max:255'],
            'representante_rut' => ['nullable', 'string', 'max:20', new Rut],
            'representante_cargo' => ['nullable', 'string', 'max:100'],
            'representante_email' => ['nullable', 'email', 'max:255'],
        ];

        return $rules;
    }

    public function dashboard(Company $company)
    {
        // Aseguramos que la empresa esté seleccionada en la sesión al entrar a su dashboard
        session(['selected_company_id' => $company->id]);

        $totalEmployees = \Modules\Employees\Models\Employee::where('company_id', $company->id)->count();
        $activeEmployees = \Modules\Employees\Models\Employee::where('company_id', $company->id)
            ->whereHas('user', function ($q) {
                $q->where('status', true);
            })->count();

        // Simulación de datos para el dashboard
        $completionAverage = \Modules\Employees\Models\Employee::where('company_id', $company->id)
            ->get()
            ->avg('completion_percentage') ?? 0;

        // Verificar si la empresa tiene un admin
        $hasAdmin = $company->users()->whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->exists();

        return view('companies::dashboard', compact('company', 'totalEmployees', 'activeEmployees', 'completionAverage', 'hasAdmin'));
    }

    public function transactions(Company $company)
    {
        $user = auth()->user();
        if (! $user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }

        return view('companies::transactions', compact('company'));
    }
}
