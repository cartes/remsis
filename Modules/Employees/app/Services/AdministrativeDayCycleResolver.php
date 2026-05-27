<?php

namespace Modules\Employees\Services;

use Carbon\Carbon;

class AdministrativeDayCycleResolver
{
    /**
     * Resolve cycle key and date range for a given date and cycle type.
     *
     * @param  mixed   $date
     * @param  string  $cycleType  'annual' or 'semiannual'
     * @return array
     */
    public function resolve($date, string $cycleType): array
    {
        $carbonDate = Carbon::parse($date);
        $year = $carbonDate->year;

        if ($cycleType === 'semiannual') {
            if ($carbonDate->month <= 6) {
                return [
                    'cycle_key' => $year . '-S1',
                    'starts_on' => Carbon::create($year, 1, 1)->startOfDay(),
                    'ends_on'   => Carbon::create($year, 6, 30)->endOfDay(),
                ];
            } else {
                return [
                    'cycle_key' => $year . '-S2',
                    'starts_on' => Carbon::create($year, 7, 1)->startOfDay(),
                    'ends_on'   => Carbon::create($year, 12, 31)->endOfDay(),
                ];
            }
        }

        // Default to annual
        return [
            'cycle_key' => (string) $year,
            'starts_on' => Carbon::create($year, 1, 1)->startOfDay(),
            'ends_on'   => Carbon::create($year, 12, 31)->endOfDay(),
        ];
    }
}
