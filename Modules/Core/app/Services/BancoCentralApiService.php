<?php

namespace Modules\Core\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BancoCentralApiService
{
    private const ENDPOINT = 'https://si3.bcentral.cl/SieteRestWS/SieteRestWS.ashx';
    private const IPC_SERIES = 'G073.IPC.VAR.2023.M';
    private const UF_SERIES = 'F073.UFF.PRE.Z.D';

    public function fetchMonthlyIpcVariation(string $username, string $password, CarbonImmutable $referencePeriod): array
    {
        $payload = $this->requestSeries(
            $username,
            $password,
            self::IPC_SERIES,
            $referencePeriod->startOfMonth(),
            $referencePeriod->endOfMonth(),
        );

        $observation = collect(data_get($payload, 'Series.Obs', []))
            ->filter(fn (array $item) => ($item['statusCode'] ?? null) === 'OK' && ($item['value'] ?? null) !== null)
            ->sortBy(fn (array $item) => $this->parseDate($item['indexDateString'])->timestamp)
            ->last();

        if (! is_array($observation)) {
            throw new RuntimeException('La API del Banco Central no devolvió observaciones válidas para el IPC mensual.');
        }

        return [
            'series_id' => self::IPC_SERIES,
            'reference_period' => $referencePeriod->startOfMonth(),
            'published_at' => $this->parseDate($observation['indexDateString']),
            'value' => $this->normalizePublishedIpc($observation['value']),
        ];
    }

    public function fetchUfValue(string $username, string $password, CarbonImmutable $date): array
    {
        try {
            $payload = $this->requestSeries($username, $password, self::UF_SERIES, $date, $date);
        } catch (RuntimeException $exception) {
            $payload = $this->requestSeries(
                $username,
                $password,
                self::UF_SERIES,
                $date->subDays(7),
                $date,
            );

            $observation = $this->extractUfObservationForDate($payload, $date);

            if (! is_array($observation)) {
                throw $exception;
            }

            return [
                'series_id' => self::UF_SERIES,
                'date' => $this->parseDate($observation['indexDateString']),
                'value' => round($this->normalizeNumeric($observation['value']), 2),
            ];
        }

        $observation = $this->extractUfObservationForDate($payload, $date);

        if (! is_array($observation)) {
            throw new RuntimeException('La API del Banco Central no devolvió la UF diaria solicitada.');
        }

        return [
            'series_id' => self::UF_SERIES,
            'date' => $this->parseDate($observation['indexDateString']),
            'value' => round($this->normalizeNumeric($observation['value']), 2),
        ];
    }

    private function requestSeries(
        string $username,
        string $password,
        string $timeSeries,
        CarbonImmutable $firstDate,
        CarbonImmutable $lastDate,
    ): array {
        $response = Http::acceptJson()
            ->timeout(20)
            ->get(self::ENDPOINT, [
                'user' => $username,
                'pass' => $password,
                'function' => 'GetSeries',
                'timeseries' => $timeSeries,
                'firstdate' => $firstDate->format('Y-m-d'),
                'lastdate' => $lastDate->format('Y-m-d'),
            ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'La API del Banco Central respondió con HTTP %s.',
                $response->status()
            ));
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('La API del Banco Central devolvió una respuesta inválida.');
        }

        if ((int) ($payload['Codigo'] ?? -1) !== 0) {
            throw new RuntimeException((string) ($payload['Descripcion'] ?? 'No fue posible consultar la API del Banco Central.'));
        }

        return $payload;
    }

    private function parseDate(string $value): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('d-m-Y', $value, 'America/Santiago')->startOfDay();
    }

    private function extractUfObservationForDate(array $payload, CarbonImmutable $date): ?array
    {
        return collect(data_get($payload, 'Series.Obs', []))
            ->filter(fn (array $item) => ($item['statusCode'] ?? null) === 'OK' && ($item['value'] ?? null) !== null)
            ->first(function (array $item) use ($date) {
                return $this->parseDate($item['indexDateString'])->isSameDay($date);
            });
    }

    private function normalizeNumeric(string|int|float $value): float
    {
        return (float) str_replace(',', '.', str_replace(' ', '', (string) $value));
    }

    private function normalizePublishedIpc(string|int|float $value): float
    {
        $rounded = round($this->normalizeNumeric($value), 1);

        return $rounded == 0.0 ? 0.0 : $rounded;
    }
}
