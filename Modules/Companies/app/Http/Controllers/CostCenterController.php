<?php

namespace Modules\Companies\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CostCenter;

class CostCenterController extends Controller
{
    /**
     * Mostrar centros de costo de una empresa
     */
    public function index(Company $company)
    {
        $user = auth()->user();
        if (!$user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }

        $costCenters = $company->costCenters()->orderBy('code')->get();

        return view('companies::cost_centers', compact('company', 'costCenters'));
    }

    /**
     * Crear nuevo centro de costo
     */
    public function store(Request $request, Company $company)
    {
        $user = auth()->user();
        if (!$user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'code.required' => 'El código es obligatorio.',
            'name.required' => 'El nombre es obligatorio.',
            'status.required' => 'El estado es obligatorio.',
        ]);

        // Verificar que el código no exista para esta empresa
        $exists = CostCenter::where('company_id', $company->id)
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'El código ya existe para esta empresa.'
            ], 422);
        }

        $costCenter = $company->costCenters()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Centro de costo creado correctamente.',
            'costCenter' => $costCenter
        ]);
    }

    /**
     * Actualizar centro de costo
     */
    public function update(Request $request, Company $company, CostCenter $costCenter)
    {
        $user = auth()->user();
        if (!$user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }

        if ($costCenter->company_id !== $company->id) {
            abort(403, 'Este centro de costo no pertenece a la empresa.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        // Verificar que el código no exista para esta empresa (excepto el actual)
        $exists = CostCenter::where('company_id', $company->id)
            ->where('code', $validated['code'])
            ->where('id', '!=', $costCenter->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'El código ya existe para esta empresa.'
            ], 422);
        }

        $costCenter->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Centro de costo actualizado correctamente.',
            'costCenter' => $costCenter
        ]);
    }

    /**
     * Eliminar centro de costo
     */
    public function destroy(Company $company, CostCenter $costCenter)
    {
        $user = auth()->user();
        if (!$user->hasRole('super-admin') && $user->company_id !== $company->id) {
            abort(403);
        }

        if ($costCenter->company_id !== $company->id) {
            abort(403, 'Este centro de costo no pertenece a la empresa.');
        }

        $costCenter->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Centro de costo eliminado correctamente.'
        ]);
    }
}
