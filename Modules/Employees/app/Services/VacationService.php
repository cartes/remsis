<?php

declare(strict_types=1);

namespace Modules\Employees\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeLog;
use Modules\Employees\Models\VacationRequest;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Users\Models\User;

/**
 * Feriado anual (Arts. 67 a 70 del Código del Trabajo).
 *
 * - Art. 67: días hábiles por año de servicio (parámetro de la empresa; 15 por defecto).
 * - Art. 68: feriado progresivo, un día adicional por cada tres nuevos años sobre los diez
 *   de trabajo; de otros empleadores se reconocen hasta diez años.
 * - Art. 69: el sábado es inhábil para contar el feriado.
 * - Art. 70: sólo se pueden acumular dos períodos; se advierte cuando el saldo los supera.
 *
 * El saldo no se guarda: se calcula desde la fecha de ingreso (o desde el saldo de apertura)
 * menos los días de las solicitudes aprobadas.
 */
class VacationService
{
    public const DEFAULT_DAYS_PER_YEAR = 15.0;

    private const PROGRESSIVE_BASE_YEARS = 10;

    private const PROGRESSIVE_YEARS_PER_DAY = 3;

    private const PREVIOUS_EMPLOYERS_CAP_MONTHS = 120;

    private const MAX_ACCUMULATED_PERIODS = 2;

    public function __construct(private readonly WorkingDayCalendar $calendar) {}

    /**
     * @return array{
     *     per_year: float, initial: float, accrued: float, progressive: float, used: float,
     *     pending: float, available: float, progressive_per_year: int, years_of_service: int,
     *     entitled: bool, exceeds_accumulation: bool, has_hire_date: bool, accrual_from: ?CarbonImmutable
     * }
     */
    public function balance(Employee $employee, ?CarbonInterface $asOf = null): array
    {
        $asOf = CarbonImmutable::parse(($asOf ?? now())->toDateString());
        $perYear = $this->daysPerYear($employee);
        $hire = $employee->hire_date ? CarbonImmutable::parse($employee->hire_date->toDateString()) : null;

        $empty = [
            'per_year' => $perYear, 'initial' => 0.0, 'accrued' => 0.0, 'progressive' => 0.0, 'used' => 0.0,
            'pending' => 0.0, 'available' => 0.0, 'progressive_per_year' => 0, 'years_of_service' => 0,
            'entitled' => false, 'exceeds_accumulation' => false, 'has_hire_date' => $hire !== null, 'accrual_from' => null,
        ];

        if ($hire === null || $hire->gt($asOf)) {
            return $empty;
        }

        $opening = $employee->vacation_initial_balance_date
            ? CarbonImmutable::parse($employee->vacation_initial_balance_date->toDateString())
            : null;
        $from = $opening && $opening->gt($hire) ? $opening : $hire;
        $initial = $opening ? (float) $employee->vacation_initial_balance : 0.0;

        $accrued = $from->gt($asOf) ? 0.0 : round($perYear / 12 * $this->monthsBetween($from, $asOf), 2);

        // Feriado progresivo: se devenga en cada aniversario según los años totales reconocidos.
        $previousMonths = min((int) $employee->previous_employment_months, self::PREVIOUS_EMPLOYERS_CAP_MONTHS);
        $yearsOfService = (int) floor($hire->diffInYears($asOf));
        $progressive = 0;
        $progressivePerYear = 0;
        for ($year = 1; $year <= $yearsOfService; $year++) {
            $days = $this->progressiveDays(intdiv($previousMonths + $year * 12, 12));
            $progressivePerYear = $days;
            if ($hire->addYears($year)->gt($from)) {
                $progressive += $days;
            }
        }

        $requests = VacationRequest::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('start_date', '>=', $from->toDateString());
        $used = (float) (clone $requests)->where('status', VacationRequest::STATUS_APPROVED)->sum('working_days');
        $pending = (float) (clone $requests)->where('status', VacationRequest::STATUS_PENDING)->sum('working_days');

        $available = round($initial + $accrued + $progressive - $used, 2);

        return [
            'per_year' => $perYear,
            'initial' => $initial,
            'accrued' => $accrued,
            'progressive' => (float) $progressive,
            'used' => $used,
            'pending' => $pending,
            'available' => $available,
            'progressive_per_year' => $progressivePerYear,
            'years_of_service' => $yearsOfService,
            'entitled' => $yearsOfService >= 1,
            'exceeds_accumulation' => $available > self::MAX_ACCUMULATED_PERIODS * ($perYear + $progressivePerYear),
            'has_hire_date' => true,
            'accrual_from' => $from,
        ];
    }

    /** Días hábiles que un rango de fechas descuenta del saldo. */
    public function workingDays(CarbonInterface $start, CarbonInterface $end): int
    {
        return count($this->calendar->workingDaysBetween($start, $end));
    }

    /**
     * Ingresa una solicitud pendiente. Quien solicita desde el portal no puede pedir más días
     * que su saldo disponible; la empresa sí puede anticipar feriado al registrarlo ella misma.
     */
    public function request(Employee $employee, string $start, string $end, ?string $notes, string $origin): VacationRequest
    {
        $startDate = CarbonImmutable::parse($start);
        $endDate = CarbonImmutable::parse($end);

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages(['end_date' => ['La fecha de término debe ser igual o posterior a la de inicio.']]);
        }

        if ($employee->hire_date && $startDate->lt($employee->hire_date)) {
            throw ValidationException::withMessages(['start_date' => ['Las vacaciones no pueden comenzar antes de la fecha de ingreso.']]);
        }

        $days = $this->workingDays($startDate, $endDate);
        if ($days === 0) {
            throw ValidationException::withMessages(['start_date' => ['El rango indicado no contiene días hábiles.']]);
        }

        $this->assertPeriodsOpen($employee, $startDate, $endDate);

        return DB::transaction(function () use ($employee, $startDate, $endDate, $days, $notes, $origin) {
            // Serializa las solicitudes del mismo colaborador para no duplicar rangos ni sobregirar el saldo.
            Employee::withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $overlaps = VacationRequest::withoutGlobalScopes()
                ->where('employee_id', $employee->id)
                ->whereIn('status', [VacationRequest::STATUS_PENDING, VacationRequest::STATUS_APPROVED])
                ->where('start_date', '<=', $endDate->toDateString())
                ->where('end_date', '>=', $startDate->toDateString())
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages(['start_date' => ['Ya existe una solicitud de vacaciones pendiente o aprobada que se cruza con esas fechas.']]);
            }

            if ($origin === VacationRequest::ORIGIN_EMPLOYEE) {
                $balance = $this->balance($employee);
                $free = $balance['available'] - $balance['pending'];
                if ($days > $free) {
                    throw ValidationException::withMessages(['start_date' => [sprintf(
                        'Solicitas %d días hábiles y tu saldo disponible es de %s (descontando solicitudes pendientes).',
                        $days,
                        self::formatDays(max(0, $free))
                    )]]);
                }
            }

            return VacationRequest::create([
                'tenant_id' => $employee->tenant_id,
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'working_days' => $days,
                'status' => VacationRequest::STATUS_PENDING,
                'origin' => $origin,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Aprueba la solicitud y marca cada día hábil como "vacaciones" en la asistencia,
     * de modo que la nómina los pague como días trabajados.
     */
    public function approve(VacationRequest $request, User $resolver): void
    {
        if ($request->status !== VacationRequest::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => ['Esta solicitud ya no se encuentra pendiente.']]);
        }

        $employee = Employee::withoutGlobalScopes()->findOrFail($request->employee_id);
        $this->assertPeriodsOpen($employee, $request->start_date, $request->end_date);

        DB::transaction(function () use ($request, $employee, $resolver) {
            Employee::withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            foreach ($this->calendar->workingDaysBetween($request->start_date, $request->end_date) as $day) {
                $record = AttendanceRecord::where('employee_id', $employee->id)
                    ->whereDate('date', $day->toDateString())
                    ->lockForUpdate()
                    ->first();

                if ($record && $record->status !== 'vacaciones') {
                    throw ValidationException::withMessages(['start_date' => [sprintf(
                        'El %s ya tiene un registro de asistencia (%s). Corrígelo antes de aprobar las vacaciones.',
                        $day->format('d/m/Y'),
                        AttendanceRecord::$statusLabels[$record->status] ?? $record->status
                    )]]);
                }

                if (! $record) {
                    AttendanceRecord::create([
                        'tenant_id' => $employee->tenant_id,
                        'company_id' => $employee->company_id,
                        'employee_id' => $employee->id,
                        'date' => $day->toDateString(),
                        'status' => 'vacaciones',
                        'is_justified' => false,
                    ]);
                }
            }

            $request->update([
                'status' => VacationRequest::STATUS_APPROVED,
                'resolved_by' => $resolver->id,
                'resolved_at' => now(),
            ]);

            $this->log($request, $resolver, sprintf(
                'Vacaciones aprobadas del %s al %s (%s días hábiles).',
                $request->start_date->format('d/m/Y'),
                $request->end_date->format('d/m/Y'),
                self::formatDays($request->working_days)
            ));
        });
    }

    public function reject(VacationRequest $request, User $resolver, string $reason): void
    {
        if ($request->status !== VacationRequest::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => ['Esta solicitud ya no se encuentra pendiente.']]);
        }

        $request->update([
            'status' => VacationRequest::STATUS_REJECTED,
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->log($request, $resolver, sprintf(
            'Vacaciones rechazadas del %s al %s. Motivo: %s',
            $request->start_date->format('d/m/Y'),
            $request->end_date->format('d/m/Y'),
            $reason
        ));
    }

    /**
     * Anula una solicitud pendiente o aprobada. Si estaba aprobada, quita los días de
     * vacaciones de la asistencia y el saldo se recupera al dejar de contarla.
     */
    public function cancel(VacationRequest $request, User $actor): void
    {
        if (! in_array($request->status, [VacationRequest::STATUS_PENDING, VacationRequest::STATUS_APPROVED], true)) {
            throw ValidationException::withMessages(['status' => ['Sólo se pueden anular solicitudes pendientes o aprobadas.']]);
        }

        $employee = Employee::withoutGlobalScopes()->findOrFail($request->employee_id);
        $wasApproved = $request->status === VacationRequest::STATUS_APPROVED;

        if ($wasApproved) {
            $this->assertPeriodsOpen($employee, $request->start_date, $request->end_date);
        }

        DB::transaction(function () use ($request, $employee, $actor, $wasApproved) {
            if ($wasApproved) {
                AttendanceRecord::where('employee_id', $employee->id)
                    ->where('status', 'vacaciones')
                    ->where('date', '>=', $request->start_date->toDateString())
                    ->where('date', '<', $request->end_date->copy()->addDay()->toDateString())
                    ->delete();
            }

            $request->update([
                'status' => VacationRequest::STATUS_CANCELLED,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
            ]);

            if ($wasApproved) {
                $this->log($request, $actor, sprintf(
                    'Vacaciones anuladas del %s al %s.',
                    $request->start_date->format('d/m/Y'),
                    $request->end_date->format('d/m/Y')
                ));
            }
        });
    }

    public static function formatDays(float|int $days): string
    {
        return rtrim(rtrim(number_format((float) $days, 2, ',', '.'), '0'), ',') ?: '0';
    }

    private function daysPerYear(Employee $employee): float
    {
        $configured = Company::withoutGlobalScopes()->whereKey($employee->company_id)->value('vacation_days_per_year');

        return (float) $configured > 0 ? (float) $configured : self::DEFAULT_DAYS_PER_YEAR;
    }

    private function progressiveDays(int $totalYears): int
    {
        return max(0, intdiv($totalYears - self::PROGRESSIVE_BASE_YEARS, self::PROGRESSIVE_YEARS_PER_DAY));
    }

    /** Meses transcurridos con fracción: meses completos más los días restantes sobre 30. */
    private function monthsBetween(CarbonImmutable $from, CarbonImmutable $to): float
    {
        $whole = (int) floor($from->diffInMonths($to));
        $rest = (int) round($from->addMonths($whole)->diffInDays($to));

        return $whole + min($rest, 30) / 30;
    }

    /** Las vacaciones se reflejan en la asistencia; no se pueden mover en meses ya cerrados. */
    private function assertPeriodsOpen(Employee $employee, CarbonInterface $start, CarbonInterface $end): void
    {
        $locked = PayrollPeriod::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('company_id', $employee->company_id)
            ->whereIn('status', [PayrollPeriod::STATUS_CLOSED, PayrollPeriod::STATUS_PAID])
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->exists();

        if ($locked) {
            throw ValidationException::withMessages(['start_date' => ['Las fechas indicadas caen en un período de nómina cerrado o pagado.']]);
        }
    }

    private function log(VacationRequest $request, User $actor, string $description): void
    {
        EmployeeLog::create([
            'employee_id' => $request->employee_id,
            'user_id' => $actor->id,
            'type' => EmployeeLog::TYPE_AUSENTISMO,
            'description' => $description,
        ]);
    }
}
