<?php

namespace Modules\Companies\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Companies\Models\Company;
use Modules\AdminPanel\Models\Ccaf;

class CompaniesController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Company::orderBy('razon_social')->orderBy('name');

        if (!$user->hasRole('super-admin')) {
            $query->where('id', $user->company_id);
        }

        $companies = $query->get();
        return view('companies::index', compact('companies'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403, 'No tiene permiso para crear empresas.');
        }
        return view('companies::create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }
        $validated = $request->validate(
            [
                'razon_social' => ['required', 'string', 'max:255'],
                'rut' => ['required', 'string', 'max:20', 'unique:companies,rut'],
            ],
            [
                'razon_social.required' => 'La razón social es obligatoria.',
                'rut.required' => 'El RUT es obligatorio.',
                'rut.unique' => 'El RUT ya está registrado en otra empresa.',
            ]
        );

        $data = $validated + ['name' => $validated['razon_social']]; // por NOT NULL en name
        $company = Company::create($data);

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
        if (!$user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }
        $ccafs = Ccaf::orderBy('nombre')->get(['id', 'nombre']);
        return view('companies::edit', compact('company', 'ccafs'));
    }

    public function update(Request $request, Company $company)
    {
        $user = auth()->user();
        if (!$user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
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
            'ccaf_id' => ['nullable', 'exists:ccafs,id'],
            'banco' => ['nullable', 'string', 'max:100'],
            'cuenta_bancaria' => ['nullable', 'string', 'max:100'],
            'representante_nombre' => ['nullable', 'string', 'max:255'],
            'representante_rut' => ['nullable', 'string', 'max:20'],
            'representante_cargo' => ['nullable', 'string', 'max:100'],
            'representante_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ], [
            'dia_pago_dia.required_if' => 'Debes indicar el día cuando el pago es “Día fijo”.',
        ]);

        $company->update($data);

        return redirect()
            ->route('companies.edit', $company)
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
            'rut' => ['required', 'string', 'max:20', Rule::unique('companies', 'rut')->ignore($company->id)],
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
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'nombre_fantasia' => ['nullable', 'string', 'max:255'],
            'rut' => ['nullable', 'string', 'max:20', Rule::unique('companies', 'rut')->ignore($ignoreId)],
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

            // Banco/cuenta si los mantienes
            'banco' => ['nullable', 'string', 'max:100'],
            'cuenta_bancaria' => ['nullable', 'string', 'max:100'],

            // Representante
            'representante_nombre' => ['nullable', 'string', 'max:255'],
            'representante_rut' => ['nullable', 'string', 'max:20'],
            'representante_cargo' => ['nullable', 'string', 'max:100'],
            'representante_email' => ['nullable', 'email', 'max:255'],

            'notes' => ['nullable', 'string'],
        ];
    }
}
