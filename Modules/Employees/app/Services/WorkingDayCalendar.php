<?php

declare(strict_types=1);

namespace Modules\Employees\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Días hábiles para el feriado anual: lunes a viernes que no sean festivos.
 * El sábado se considera siempre inhábil (Art. 69 CT).
 */
class WorkingDayCalendar
{
    public function isWorkingDay(CarbonInterface $date): bool
    {
        return ! $date->isWeekend() && ! $this->isHoliday($date);
    }

    public function isHoliday(CarbonInterface $date): bool
    {
        return isset(config('core.holidays.'.$date->year, [])[$date->toDateString()]);
    }

    /** Indica si el año tiene su calendario de festivos cargado. */
    public function hasCalendarFor(int $year): bool
    {
        return ! empty(config('core.holidays.'.$year));
    }

    /**
     * @return list<CarbonImmutable>
     */
    public function workingDaysBetween(CarbonInterface $start, CarbonInterface $end): array
    {
        $days = [];
        $day = CarbonImmutable::parse($start->toDateString());
        $last = CarbonImmutable::parse($end->toDateString());

        while ($day->lte($last)) {
            if ($this->isWorkingDay($day)) {
                $days[] = $day;
            }
            $day = $day->addDay();
        }

        return $days;
    }
}
