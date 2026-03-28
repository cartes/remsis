<?php

namespace Modules\Companies\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Companies\Models\Company;
use Modules\Payroll\Models\Item;

class CompanyItemsController extends Controller
{
    public function index(Company $company)
    {
        $this->authorizeCompanyAccess($company);

        $items = Item::forCompany($company->id)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        // JSON para llamadas AJAX (selector de la ficha del colaborador)
        if (request()->expectsJson()) {
            return response()->json($items);
        }

        return view('companies::items.index', compact('company', 'items'));
    }

    public function store(Request $request, Company $company)
    {
        $this->authorizeCompanyAccess($company);

        $validated = $request->validate([
            'name'                  => 'required|string|max:150',
            'code'                  => 'nullable|string|max:50|unique:items,code,NULL,id,company_id,' . $company->id,
            'type'                  => 'required|in:haber_imponible,haber_no_imponible,descuento_legal,descuento_varios,credito',
            'calculation_type'      => 'required|in:fijo,proporcional_ausencia,liquido',
            'assignment_type'       => 'required|in:igual_para_todos,distinto_por_persona',
            'currency'              => 'required|in:CLP,UF,UTM',
            'default_amount'        => 'nullable|numeric|min:0|required_if:assignment_type,igual_para_todos',
            'is_taxable'            => 'boolean',
            'is_gratification_base' => 'boolean',
            'is_overtime_base'      => 'boolean',
        ]);

        // Solo guarda default_amount si aplica
        if (($validated['assignment_type'] ?? '') !== 'igual_para_todos') {
            $validated['default_amount'] = null;
        }

        $item = Item::create(array_merge($validated, ['company_id' => $company->id]));

        return response()->json(['success' => true, 'item' => $item->fresh()], 201);
    }

    public function update(Request $request, Company $company, Item $item)
    {
        $this->authorizeCompanyAccess($company);
        abort_if($item->company_id !== $company->id, 403);

        $validated = $request->validate([
            'name'                  => 'required|string|max:150',
            'code'                  => 'nullable|string|max:50|unique:items,code,' . $item->id . ',id,company_id,' . $company->id,
            'type'                  => 'required|in:haber_imponible,haber_no_imponible,descuento_legal,descuento_varios,credito',
            'calculation_type'      => 'required|in:fijo,proporcional_ausencia,liquido',
            'assignment_type'       => 'required|in:igual_para_todos,distinto_por_persona',
            'currency'              => 'required|in:CLP,UF,UTM',
            'default_amount'        => 'nullable|numeric|min:0|required_if:assignment_type,igual_para_todos',
            'is_taxable'            => 'boolean',
            'is_gratification_base' => 'boolean',
            'is_overtime_base'      => 'boolean',
        ]);

        if (($validated['assignment_type'] ?? '') !== 'igual_para_todos') {
            $validated['default_amount'] = null;
        }

        $item->update($validated);

        return response()->json(['success' => true, 'item' => $item->fresh()]);
    }

    public function destroy(Company $company, Item $item)
    {
        $this->authorizeCompanyAccess($company);
        abort_if($item->company_id !== $company->id, 403);

        if ($item->employeeItems()->where('is_active', true)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un concepto que tiene asignaciones activas en colaboradores.',
            ], 422);
        }

        $item->delete();

        return response()->json(['success' => true]);
    }

    private function authorizeCompanyAccess(Company $company): void
    {
        $user = auth()->user();

        if (! $user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }
    }
}

