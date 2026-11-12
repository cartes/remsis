<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Cliente HTTP para sincronización de indicadores económicos chilenos (UF, UTM, IPC)
 * desde mindicador.cl con soporte de fallback hacia la API de la CMF.
 */
class MindicadorApiService
{
    private const BASE_URL = 'https://mindicador.cl/api';

    private const CMF_BASE_URL = 'https://api.cmfchile.cl/api-sbifv3/recursos_api';

    /**
     * Obtiene el resumen de indicadores vigentes del día desde mindicador.cl.
     *
     * @return array{uf: float, utm: float, ipc: float, dolar: float|null}
     */
    public function getTodayIndicators(): array
    {
        $response = Http::timeout(10)
            ->retry(2, 150, throw: false)
            ->acceptJson()
            ->get(self::BASE_URL);

        if (! $response->successful()) {
            throw new RuntimeException('No fue posible obtener los indicadores diarios desde mindicador.cl.');
        }

        $payload = $response->json();

        if (! is_array($payload) || ! isset($payload['uf']['valor'], $payload['utm']['valor'])) {
            throw new RuntimeException('Respuesta inválida desde mindicador.cl al consultar indicadores diarios.');
        }

        return [
            'uf' => (float) $payload['uf']['valor'],
            'utm' => (float) $payload['utm']['valor'],
            'ipc' => isset($payload['ipc']['valor']) ? (float) $payload['ipc']['valor'] : 0.0,
            'dolar' => isset($payload['dolar']['valor']) ? (float) $payload['dolar']['valor'] : null,
        ];
    }

    /**
     * Obtiene el valor de un indicador ('uf', 'utm', 'ipc') para una fecha específica.
     * Si mindicador.cl falla y existe API Key de CMF configurada, utiliza CMF como fallback.
     */
    public function getIndicatorForDate(string $indicator, CarbonInterface $date): float
    {
        $normalizedIndicator = mb_strtolower(trim($indicator));
        $formattedDate = $date->format('d-m-Y');

        try {
            $response = Http::timeout(10)
                ->retry(2, 150, throw: false)
                ->acceptJson()
                ->get(sprintf('%s/%s/%s', self::BASE_URL, $normalizedIndicator, $formattedDate));

            if ($response->successful()) {
                $serie = $response->json('serie');
                if (is_array($serie) && ! empty($serie[0]['valor']) && is_numeric($serie[0]['valor'])) {
                    return (float) $serie[0]['valor'];
                }
            }
        } catch (Throwable) {
            // Continuar hacia fallback CMF
        }

        return $this->fetchFromCmfFallback($normalizedIndicator, $date);
    }

    /**
     * Obtiene la serie histórica anual de un indicador desde mindicador.cl.
     *
     * @return array<int, array{fecha: string, valor: float}>
     */
    public function getHistoricalIndicator(string $indicator, int $year): array
    {
        $normalizedIndicator = mb_strtolower(trim($indicator));

        $response = Http::timeout(10)
            ->retry(2, 150, throw: false)
            ->acceptJson()
            ->get(sprintf('%s/%s/%d', self::BASE_URL, $normalizedIndicator, $year));

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'No fue posible obtener el historial anual de %s (%d) desde mindicador.cl.',
                $normalizedIndicator,
                $year
            ));
        }

        $serie = $response->json('serie');

        if (! is_array($serie)) {
            return [];
        }

        return array_map(static fn (array $row): array => [
            'fecha' => (string) ($row['fecha'] ?? ''),
            'valor' => (float) ($row['valor'] ?? 0.0),
        ], $serie);
    }

    /**
     * Fallback hacia la API de la Comisión para el Mercado Financiero (CMF).
     */
    protected function fetchFromCmfFallback(string $indicator, CarbonInterface $date): float
    {
        $apiKey = (string) config('services.cmf.key', env('CMF_API_KEY', ''));

        if ($apiKey === '') {
            throw new RuntimeException(sprintf(
                'No se pudo obtener el indicador "%s" para %s desde mindicador.cl y no hay CMF_API_KEY configurada.',
                $indicator,
                $date->toDateString()
            ));
        }

        $endpoint = match ($indicator) {
            'uf' => sprintf(
                '%s/uf/%s/%s/dias/%s',
                self::CMF_BASE_URL,
                $date->format('Y'),
                $date->format('m'),
                $date->format('d')
            ),
            'utm' => sprintf(
                '%s/utm/%s/%s',
                self::CMF_BASE_URL,
                $date->format('Y'),
                $date->format('m')
            ),
            'ipc' => sprintf(
                '%s/ipc/%s/%s',
                self::CMF_BASE_URL,
                $date->format('Y'),
                $date->format('m')
            ),
            default => throw new RuntimeException(sprintf('Indicador "%s" no soportado en fallback CMF.', $indicator)),
        };

        $response = Http::timeout(10)
            ->acceptJson()
            ->get($endpoint, [
                'apikey' => $apiKey,
                'formato' => 'json',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf('Fallback CMF falló al consultar %s.', $indicator));
        }

        $collectionKey = match ($indicator) {
            'uf' => 'UFs',
            'utm' => 'UTMs',
            'ipc' => 'IPCs',
        };

        $rawValue = $response->json(sprintf('%s.0.Valor', $collectionKey));

        if (! is_string($rawValue) && ! is_numeric($rawValue)) {
            throw new RuntimeException(sprintf('Respuesta CMF sin valor válido para %s.', $indicator));
        }

        // CMF formatea miles con punto y decimales con coma (ej: "38.450,12")
        $normalized = str_replace(['.', ','], ['', '.'], (string) $rawValue);

        return (float) $normalized;
    }
}

