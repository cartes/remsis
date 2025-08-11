<?php

namespace Modules\Companies\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Companies\Models\Company;

class CompaniesController extends Controller
{
    public function index()
    {
        $companies = Company::orderBy('razon_social')->orderBy('name')->get();
        return view('companies::index', compact('companies'));
    }

    public function create()
    {
        return view('companies::create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'max:20', 'unique:companies,rut'],
        ]);

        $company = \Modules\Companies\Models\Company::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Empresa creada. Completa la ficha debajo.',
            'id' => $company->id
        ], 201);
    }

    public function edit($id)
    {
        $company = Company::findOrFail($id);
        return view('companies::edit', compact('company'));
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate($this->rules($company->id));

        $company->update($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Empresa actualizada correctamente.');
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
            // Identificación
            'name' => ['nullable', 'string', 'max:255'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'nombre_fantasia' => ['nullable', 'string', 'max:255'],
            'rut' => [
                'nullable',
                'string',
                'max:20',
                // único si se informa, ignorando el propio id en update
                Rule::unique('companies', 'rut')->ignore($ignoreId),
            ],
            'giro' => ['nullable', 'string', 'max:255'],

            // Contacto/Dirección
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'comuna' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],

            // Remuneraciones
            'tipo_contribuyente' => ['nullable', Rule::in(['natural', 'juridica'])],
            'ccaf' => ['nullable', 'string', 'max:100'],   // luego FK
            'mutual' => ['nullable', 'string', 'max:100'],   // luego FK
            'dia_pago' => ['nullable', Rule::in(['ultimo_dia_habil', 'dia_fijo', 'quincenal'])],
            'dia_pago_dia' => ['nullable', 'integer', 'min:1', 'max:31', 'required_if:dia_pago,dia_fijo'],

            // Bancos
            'banco' => ['nullable', 'string', 'max:100'],
            'cuenta_bancaria' => ['nullable', 'string', 'max:100'],

            // Representante
            'representante_nombre' => ['nullable', 'string', 'max:255'],
            'representante_rut' => ['nullable', 'string', 'max:20'],
            'representante_cargo' => ['nullable', 'string', 'max:100'],
            'representante_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
