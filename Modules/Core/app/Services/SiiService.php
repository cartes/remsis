<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Http;
use Modules\Core\Models\EconomicActivity;

class SiiService
{
    /**
     * Busca los datos de un contribuyente en el SII usando su RUT.
     * Soporta diferentes drivers vía configuración (.env: SII_DRIVER).
     *
     * @param string $rut RUT a consultar (ej: 76.123.456-7)
     * @return array
     */
    public function fetchCompanyDataByRut(string $rut): array
    {
        $cleanRut = $this->cleanRut($rut);
        $driver = config('services.sii.driver', 'mock');

        if ($driver === 'api') {
            return $this->fetchFromExternalApi($cleanRut);
        }

        return $this->getMockData($cleanRut);
    }

    /**
     * Limpia el RUT quitando puntos y espacios, manteniendo el guion.
     */
    private function cleanRut(string $rut): string
    {
        $clean = preg_replace('/[^0-9kK]/', '', $rut);
        if (strlen($clean) < 2) {
            return $clean;
        }

        $num = substr($clean, 0, -1);
        $dv = substr($clean, -1);

        return strtolower($num . '-' . $dv);
    }

    /**
     * Consulta una API externa comunitaria para obtener datos del SII.
     */
    private function fetchFromExternalApi(string $rut): array
    {
        try {
            // Usamos un endpoint de SRE / API comunitaria chilena para consulta de RUT
            $response = Http::timeout(10)->get("https://api.sre.cl/api/sii/rut/{$rut}");

            if ($response->successful()) {
                $data = $response->json();
                $razonSocial = $data['razon_social'] ?? $data['nombre'] ?? '';
                $giro = $data['giro'] ?? '';
                $activities = $data['actividades'] ?? [];

                return $this->normalizeResult($razonSocial, $giro, $activities);
            }
        } catch (\Exception $e) {
            // Loguear error y caer en fallback
            logger()->error('Error consultando API externa de SII: ' . $e->getMessage());
        }

        // Fallback a mock en caso de falla o límite de tasa de la API pública
        return $this->getMockData($rut);
    }

    /**
     * Normaliza los resultados mapeando los códigos de actividad con la base de datos local.
     */
    private function normalizeResult(string $razonSocial, string $giro, array $activities): array
    {
        $mappedActivities = [];

        foreach ($activities as $act) {
            $code = (string) ($act['codigo'] ?? $act['code'] ?? '');
            if (empty($code)) {
                continue;
            }

            // Buscar en la base de datos local
            $localActivity = EconomicActivity::where('code', $code)->first();

            $mappedActivities[] = [
                'code' => $code,
                'name' => $localActivity ? $localActivity->name : ($act['nombre'] ?? $act['name'] ?? 'Actividad Desconocida'),
                'id' => $localActivity ? $localActivity->id : null,
            ];
        }

        return [
            'razon_social' => mb_strtoupper($razonSocial, 'UTF-8'),
            'giro' => mb_strtoupper($giro, 'UTF-8'),
            'economic_activities' => $mappedActivities,
            'primary_activity' => $mappedActivities[0] ?? null,
        ];
    }

    /**
     * Genera datos de prueba realistas para desarrollo.
     */
    private function getMockData(string $rut): array
    {
        // Limpiamos formato para el switch
        $clean = str_replace('-', '', $rut);

        // Retornar datos en base al RUT ingresado
        switch ($clean) {
            case '761234567':
                $razonSocial = 'Servicios Tecnológicos Remsis SpA';
                $giro = 'SERVICIOS INFORMÁTICOS Y DESARROLLO DE SOFTWARE';
                $activities = [['codigo' => '620100', 'nombre' => 'ACTIVIDADES DE PROGRAMACIÓN INFORMÁTICA']];
                break;

            case '76999888k':
            case '76999888K':
                $razonSocial = 'Asesorías Contables Remuneradas Limitada';
                $giro = 'ASESORÍA TRIBUTARIA Y CONTABLE';
                $activities = [['codigo' => '692000', 'nombre' => 'ACTIVIDADES DE CONTABILIDAD, TENEDURÍA DE LIBROS Y AUDITORÍA']];
                break;

            case '775554443':
                $razonSocial = 'Constructora Andes del Sur S.A.';
                $giro = 'CONSTRUCCIÓN DE EDIFICIOS';
                $activities = [['codigo' => '410010', 'nombre' => 'CONSTRUCCIÓN DE EDIFICIOS RESIDENCIALES']];
                break;

            default:
                // Generar un mock por defecto usando el RUT
                $razonSocial = 'EMPRESA TENANT DEMO ' . strtoupper(substr($clean, 0, 4)) . ' SPA';
                $giro = 'SERVICIOS GENERALES DE CONSULTORÍA Y GIROS VARIOS';
                
                // Intentar agarrar una actividad de la base de datos al azar o usar código 620100
                $randomActivity = EconomicActivity::inRandomOrder()->first();
                if ($randomActivity) {
                    $activities = [['codigo' => $randomActivity->code, 'nombre' => $randomActivity->name]];
                } else {
                    $activities = [['codigo' => '620100', 'nombre' => 'ACTIVIDADES DE PROGRAMACIÓN INFORMÁTICA']];
                }
                break;
        }

        return $this->normalizeResult($razonSocial, $giro, $activities);
    }
}
