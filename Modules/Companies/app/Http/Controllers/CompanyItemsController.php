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

        return response()->json($items);
    }

    public function store(Request $request, Company $company)
    {
        $this->authorizeCompanyAccess($company);

        $validated = $request->validate([
            'name'                  => 'required|string|max:150',
            'code'                  => 'nullable|string|max:50|unique:items,code,NULL,id,company_id,' . $company->id,
            'type'                  => 'required|in:haber_imponible,haber_no_imponible,descuento_legal,descuento_varios,credito',
            'is_taxable'            => 'boolean',
            'is_gratification_base' => 'boolean',
        ]);

        $item = Item::create(array_merge($validated, ['company_id' => $company->id]));

        return response()->json(['success' => true, 'item' => $item], 201);
    }

    public function update(Request $request, Company $company, Item $item)
    {
        $this->authorizeCompanyAccess($company);

        if ($item->company_id !== $company->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'                  => 'sometimes|string|max:150',
            'type'                  => 'sometimes|in:haber_imponible,haber_no_imponible,descuento_legal,descuento_varios,credito',
            'is_taxable'            => 'boolean',
            'is_gratification_base' => 'boolean',
        ]);

        $item->update($validated);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function destroy(Company $company, Item $item)
    {
        $this->authorizeCompanyAccess($company);

        if ($item->company_id !== $company->id) {
            abort(403);
        }

        if ($item->employeeItems()->where('is_active', true)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un ítem con asignaciones activas.',
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
