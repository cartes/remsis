<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\Companies\Models\Company;
use Modules\Core\Database\Seeders\LreConceptsSeeder;
use Modules\Core\Models\LreConcept;
use Modules\Payroll\Models\Item;

class CompanyItemsController extends Controller
{
    public function index(Company $company)
    {
        $this->authorizeCompanyAccess($company);

        if (Schema::hasTable('lre_concepts_dictionary') && LreConcept::query()->count() === 0) {
            app(LreConceptsSeeder::class)->run();
        }

        $items = Item::forCompany($company->id)
            ->with('lreConcept')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $lreConcepts = Schema::hasTable('lre_concepts_dictionary')
            ? LreConcept::query()->active()->orderBy('code')->get()
            : collect();

        // JSON para llamadas AJAX (selector de la ficha del colaborador)
        if (request()->expectsJson()) {
            return response()->json($items);
        }

        return view('companies::items.index', compact('company', 'items', 'lreConcepts'));
    }

    public function store(Request $request, Company $company)
    {
        $this->authorizeCompanyAccess($company);

        $validated = $request->validate([
            'lre_concept_id' => 'nullable|exists:lre_concepts_dictionary,id',
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50|unique:items,code,NULL,id,company_id,'.$company->id,
            'type' => 'required|in:haber_imponible,haber_no_imponible,descuento_legal,descuento_varios,credito',
            'calculation_type' => 'required|in:fijo,proporcional_ausencia,liquido',
            'assignment_type' => 'required|in:igual_para_todos,distinto_por_persona',
            'currency' => 'required|in:CLP,UF,UTM',
            'default_amount' => 'nullable|numeric|min:0|required_if:assignment_type,igual_para_todos',
            'is_taxable' => 'boolean',
            'is_gratification_base' => 'boolean',
            'is_overtime_base' => 'boolean',
        ]);

        // Solo guarda default_amount si aplica
        if (($validated['assignment_type'] ?? '') !== 'igual_para_todos') {
            $validated['default_amount'] = null;
        }

        $item = Item::create(array_merge($validated, [
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
        ]));

        return response()->json(['success' => true, 'item' => $item->fresh('lreConcept')], 201);
    }

    public function update(Request $request, Company $company, Item $item)
    {
        $this->authorizeCompanyAccess($company);
        abort_if($item->company_id !== $company->id, 403);

        $validated = $request->validate([
            'lre_concept_id' => 'nullable|exists:lre_concepts_dictionary,id',
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50|unique:items,code,'.$item->id.',id,company_id,'.$company->id,
            'type' => 'required|in:haber_imponible,haber_no_imponible,descuento_legal,descuento_varios,credito',
            'calculation_type' => 'required|in:fijo,proporcional_ausencia,liquido',
            'assignment_type' => 'required|in:igual_para_todos,distinto_por_persona',
            'currency' => 'required|in:CLP,UF,UTM',
            'default_amount' => 'nullable|numeric|min:0|required_if:assignment_type,igual_para_todos',
            'is_taxable' => 'boolean',
            'is_gratification_base' => 'boolean',
            'is_overtime_base' => 'boolean',
        ]);

        if (($validated['assignment_type'] ?? '') !== 'igual_para_todos') {
            $validated['default_amount'] = null;
        }

        $item->update($validated);

        return response()->json(['success' => true, 'item' => $item->fresh('lreConcept')]);
    }

    /**
     * Importa el catálogo base de conceptos chilenos vinculados al diccionario LRE DT para la empresa.
     */
    public function seedLreTemplate(Company $company)
    {
        $this->authorizeCompanyAccess($company);

        if (Schema::hasTable('lre_concepts_dictionary') && LreConcept::query()->count() === 0) {
            app(LreConceptsSeeder::class)->run();
        }

        $templates = [
            [
                'code' => 'BON_PROD',
                'name' => 'Bono de Producción / Metas',
                'type' => Item::TYPE_HABER_IMPONIBLE,
                'lre_code' => '1106',
                'calculation_type' => Item::CALC_FIJO,
                'assignment_type' => Item::ASSIGN_DISTINTO_POR_PERSONA,
                'currency' => 'CLP',
                'is_taxable' => true,
                'is_gratification_base' => true,
                'is_overtime_base' => false,
            ],
            [
                'code' => 'COMISIONES',
                'name' => 'Comisiones por Ventas',
                'type' => Item::TYPE_HABER_IMPONIBLE,
                'lre_code' => '1103',
                'calculation_type' => Item::CALC_FIJO,
                'assignment_type' => Item::ASSIGN_DISTINTO_POR_PERSONA,
                'currency' => 'CLP',
                'is_taxable' => true,
                'is_gratification_base' => true,
                'is_overtime_base' => false,
            ],
            [
                'code' => 'COLACION',
                'name' => 'Asignación de Colación',
                'type' => Item::TYPE_HABER_NO_IMPONIBLE,
                'lre_code' => '2101',
                'calculation_type' => Item::CALC_PROPORCIONAL_AUSENCIA,
                'assignment_type' => Item::ASSIGN_DISTINTO_POR_PERSONA,
                'currency' => 'CLP',
                'is_taxable' => false,
                'is_gratification_base' => false,
                'is_overtime_base' => false,
            ],
            [
                'code' => 'MOVILIZACION',
                'name' => 'Asignación de Movilización',
                'type' => Item::TYPE_HABER_NO_IMPONIBLE,
                'lre_code' => '2102',
                'calculation_type' => Item::CALC_PROPORCIONAL_AUSENCIA,
                'assignment_type' => Item::ASSIGN_DISTINTO_POR_PERSONA,
                'currency' => 'CLP',
                'is_taxable' => false,
                'is_gratification_base' => false,
                'is_overtime_base' => false,
            ],
            [
                'code' => 'VIATICOS',
                'name' => 'Viáticos',
                'type' => Item::TYPE_HABER_NO_IMPONIBLE,
                'lre_code' => '2103',
                'calculation_type' => Item::CALC_FIJO,
                'assignment_type' => Item::ASSIGN_DISTINTO_POR_PERSONA,
                'currency' => 'CLP',
                'is_taxable' => false,
                'is_gratification_base' => false,
                'is_overtime_base' => false,
            ],
            [
                'code' => 'ANTICIPO',
                'name' => 'Anticipo de Sueldo',
                'type' => Item::TYPE_DESCUENTO_VARIOS,
                'lre_code' => '3201',
                'calculation_type' => Item::CALC_FIJO,
                'assignment_type' => Item::ASSIGN_DISTINTO_POR_PERSONA,
                'currency' => 'CLP',
                'is_taxable' => false,
                'is_gratification_base' => false,
                'is_overtime_base' => false,
            ],
            [
                'code' => 'CRED_CCAF',
                'name' => 'Crédito Social CCAF',
                'type' => Item::TYPE_CREDITO,
                'lre_code' => '3203',
                'calculation_type' => Item::CALC_FIJO,
                'assignment_type' => Item::ASSIGN_DISTINTO_POR_PERSONA,
                'currency' => 'CLP',
                'is_taxable' => false,
                'is_gratification_base' => false,
                'is_overtime_base' => false,
            ],
        ];

        $lreByCode = LreConcept::query()->pluck('id', 'code');

        foreach ($templates as $tpl) {
            $lreId = $lreByCode[$tpl['lre_code']] ?? null;
            unset($tpl['lre_code']);

            Item::withTrashed()->updateOrCreate(
                ['company_id' => $company->id, 'code' => $tpl['code']],
                array_merge($tpl, [
                    'tenant_id' => $company->tenant_id,
                    'lre_concept_id' => $lreId,
                    'deleted_at' => null,
                ])
            );
        }

        $items = Item::forCompany($company->id)
            ->with('lreConcept')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Plantilla normativa LRE DT importada exitosamente.',
            'items' => $items,
        ]);
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

        if (! $user->hasRole('super-admin') && $user->company_id !== $company->id && ! $user->companies()->whereKey($company->id)->exists()) {
            abort(403);
        }
    }
}
