<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\EconomicActivity;

class EconomicActivityController extends Controller
{
    /**
     * Search economic activities for the searchable select component.
     */
    public function search(Request $request)
    {
        $query = $request->query('query', '');

        if (strlen($query) < 2 && empty($request->query('all'))) {
            return response()->json([]);
        }

        $activities = EconomicActivity::query()
            ->when($query, function ($q) use ($query) {
                $search = mb_strtoupper((string) $query, 'UTF-8');
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'code', 'name']);

        return response()->json($activities);
    }

    /**
     * Consult SII data by RUT.
     */
    public function consultRut(string $rut, \Modules\Core\Services\SiiService $siiService)
    {
        // Limpiar el RUT para una mínima validación
        $cleanRut = preg_replace('/[^0-9kK]/', '', $rut);
        if (strlen($cleanRut) < 8) {
            return response()->json([
                'status' => 'error',
                'message' => 'RUT inválido o muy corto.',
            ], 422);
        }

        try {
            $data = $siiService->fetchCompanyDataByRut($rut);
            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar datos en el SII: ' . $e->getMessage(),
            ], 500);
        }
    }
}
