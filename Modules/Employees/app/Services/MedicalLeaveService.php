<?php

declare(strict_types=1);

namespace Modules\Employees\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeLog;
use Modules\Employees\Models\EmploymentContract;
use Modules\Employees\Models\MedicalLeave;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Users\Models\User;

/**
 * Licencias médicas y subsidio por incapacidad laboral.
 *
 * - Al registrar una licencia, cada día corrido queda marcado como "licencia" en la asistencia:
 *   la nómina no paga esos días, porque los cubre el subsidio de la entidad pagadora.
 * - Carencia (Art. 14 DFL 44): en licencias de origen común de hasta 10 días el subsidio se
 *   paga desde el cuarto día; con 11 o más, desde el primero. Las licencias continuas por el
 *   mismo diagnóstico se suman para determinarla.
 * - La resolución (autorizada, reducida o rechazada) cambia los días con derecho a subsidio,
 *   no la asistencia: los días no autorizados siguen sin trabajarse y la empresa decide en la
 *   pestaña de asistencia cómo los califica.
 */
class MedicalLeaveService
{
    /** Tipos de licencia a los que se aplica la carencia. */
    private const WAITING_PERIOD_TYPES = [MedicalLeave::TYPE_COMMON, MedicalLeave::TYPE_PREGNANCY_PATHOLOGY];

    private const WAITING_PERIOD_DAYS = 3;

    private const WAITING_PERIOD_MAX_LEAVE_DAYS = 10;

    private const ATTENDANCE_REASON = 'Licencia médica folio %s';

    public function __construct(
        private readonly MedicalLeaveSubsidyCalculator $calculator,
        private readonly AdministrativeDayAttendanceService $attendance,
    ) {}

    /**
     * @param  array{folio: string, type: string, start_date: string, days: int|string, is_continuation?: bool, notes?: ?string}  $data
     */
    public function register(Employee $employee, array $data, User $actor): MedicalLeave
    {
        $start = CarbonImmutable::parse($data['start_date'])->startOfDay();
        $days = (int) $data['days'];
        $end = $start->addDays($days - 1);
        $folio = trim((string) $data['folio']);

        if ($employee->hire_date && $start->lt($employee->hire_date)) {
            throw ValidationException::withMessages(['start_date' => ['La licencia no puede comenzar antes de la fecha de ingreso.']]);
        }

        $this->assertPeriodsOpen($employee, $start, $end);

        return DB::transaction(function () use ($employee, $data, $actor, $start, $days, $end, $folio) {
            // Serializa las licencias del mismo colaborador para no duplicar rangos.
            Employee::withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $active = fn () => MedicalLeave::withoutGlobalScopes()
                ->where('status', '!=', MedicalLeave::STATUS_CANCELLED);

            if ($active()->where('company_id', $employee->company_id)->where('folio', $folio)->exists()) {
                throw ValidationException::withMessages(['folio' => ['Ya existe una licencia registrada con ese folio.']]);
            }

            $overlaps = $active()
                ->where('employee_id', $employee->id)
                ->where('start_date', '<=', $end->toDateString())
                ->where('end_date', '>=', $start->toDateString())
                ->exists();
            if ($overlaps) {
                throw ValidationException::withMessages(['start_date' => ['Ya existe una licencia médica que se cruza con esas fechas.']]);
            }

            $previous = null;
            if (! empty($data['is_continuation'])) {
                $previous = $active()
                    ->where('employee_id', $employee->id)
                    ->whereDate('end_date', $start->subDay()->toDateString())
                    ->first();

                if (! $previous) {
                    throw ValidationException::withMessages(['is_continuation' => [sprintf(
                        'No hay una licencia que termine el %s. Sólo es continuación la que sigue sin interrupción a la anterior.',
                        $start->subDay()->format('d/m/Y')
                    )]]);
                }
                if ($previous->type !== $data['type']) {
                    throw ValidationException::withMessages(['is_continuation' => ['La continuación debe ser del mismo tipo que la licencia anterior.']]);
                }
            }

            $this->markAttendance($employee, $start, $end, $folio);

            [$payerType, $payerName] = $this->payerFor($employee, $data['type']);
            $leave = MedicalLeave::create([
                'tenant_id' => $employee->tenant_id,
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'continues_leave_id' => $previous?->id,
                'folio' => $folio,
                'type' => $data['type'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days' => $days,
                'status' => MedicalLeave::STATUS_IN_PROCESS,
                'payer_type' => $payerType,
                'payer_name' => $payerName,
                'notes' => $data['notes'] ?? null,
                'registered_by' => $actor->id,
            ]);

            $this->refreshSubsidy($leave);

            $this->log($leave, $actor, sprintf(
                'Licencia médica folio %s registrada del %s al %s (%d días, %s).',
                $folio,
                $start->format('d/m/Y'),
                $end->format('d/m/Y'),
                $days,
                mb_strtolower($leave->type_label)
            ));

            return $leave->refresh();
        });
    }

    /**
     * Registra la resolución de la entidad. Se puede volver a resolver, por ejemplo tras una apelación.
     */
    public function resolve(MedicalLeave $leave, string $status, ?int $authorizedDays, ?string $resolutionDate, ?string $notes, User $actor): void
    {
        if ($leave->status === MedicalLeave::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['status' => ['La licencia está anulada.']]);
        }
        if (! in_array($status, MedicalLeave::RESOLUTION_STATUSES, true)) {
            throw ValidationException::withMessages(['status' => ['La resolución indicada no es válida.']]);
        }
        if ($status === MedicalLeave::STATUS_REDUCED && ($authorizedDays === null || $authorizedDays < 1 || $authorizedDays >= $leave->days)) {
            throw ValidationException::withMessages(['authorized_days' => [sprintf(
                'Una licencia reducida debe autorizar entre 1 y %d días.',
                $leave->days - 1
            )]]);
        }

        DB::transaction(function () use ($leave, $status, $authorizedDays, $resolutionDate, $notes, $actor) {
            $leave->update([
                'status' => $status,
                'authorized_days' => $status === MedicalLeave::STATUS_REDUCED ? $authorizedDays : null,
                'resolution_date' => $resolutionDate ?: now()->toDateString(),
                'resolution_notes' => $notes,
                'resolved_by' => $actor->id,
            ]);

            $this->recalculateChain($leave);

            $this->log($leave, $actor, sprintf(
                'Licencia médica folio %s: %s%s.',
                $leave->folio,
                mb_strtolower($leave->status_label),
                $status === MedicalLeave::STATUS_REDUCED ? sprintf(' a %d de %d días', $authorizedDays, $leave->days) : ''
            ));
        });
    }

    /**
     * Anula una licencia registrada por error: libera la asistencia y deja de contarla.
     */
    public function cancel(MedicalLeave $leave, User $actor): void
    {
        if ($leave->status === MedicalLeave::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['status' => ['La licencia ya está anulada.']]);
        }

        $employee = Employee::withoutGlobalScopes()->findOrFail($leave->employee_id);
        $this->assertPeriodsOpen($employee, $leave->start_date, $leave->end_date);

        DB::transaction(function () use ($leave, $employee, $actor) {
            AttendanceRecord::where('employee_id', $employee->id)
                ->where('status', 'licencia')
                ->where('date', '>=', $leave->start_date->toDateString())
                ->where('date', '<', $leave->end_date->copy()->addDay()->toDateString())
                ->delete();

            $previous = $leave->continues_leave_id
                ? MedicalLeave::withoutGlobalScopes()->find($leave->continues_leave_id)
                : null;
            $next = MedicalLeave::withoutGlobalScopes()->where('continues_leave_id', $leave->id)->get();

            $leave->update([
                'status' => MedicalLeave::STATUS_CANCELLED,
                'continues_leave_id' => null,
                'subsidy_days' => 0,
                'subsidy_amount' => null,
                'resolved_by' => $actor->id,
            ]);

            // Las licencias que la continuaban quedan como licencias independientes.
            foreach ($next as $orphan) {
                $orphan->update(['continues_leave_id' => null]);
                $this->refreshSubsidy($orphan);
            }
            if ($previous) {
                $this->recalculateChain($previous);
            }

            $this->log($leave, $actor, sprintf(
                'Licencia médica folio %s anulada (%s al %s).',
                $leave->folio,
                $leave->start_date->format('d/m/Y'),
                $leave->end_date->format('d/m/Y')
            ));
        });
    }

    /**
     * Vuelve a estimar el subsidio con las liquidaciones disponibles hoy. La base se fija al
     * inicio de la primera licencia de la cadena y vale para sus continuaciones.
     */
    public function refreshSubsidy(MedicalLeave $leave): void
    {
        $root = $this->root($leave);
        $employee = Employee::withoutGlobalScopes()->findOrFail($root->employee_id);
        $base = $this->calculator->base($employee, CarbonImmutable::parse($root->start_date->toDateString()), $root->id);

        $root->update([
            'subsidy_base_amount' => $base['monthly'],
            'subsidy_daily_amount' => $base['daily'],
            'subsidy_detail' => $base['detail'],
        ]);

        $this->recalculateChain($root);
    }

    /**
     * Días de licencia del período (base 30) y renta imponible que les corresponde, para las
     * cotizaciones que siguen siendo de cargo del empleador: SIS (Art. 17 DL 3.500) y su parte
     * del seguro de cesantía (Art. 8 Ley 19.728). La renta es la del mes anterior al inicio de
     * la licencia o, si no hay liquidación de ese mes, la pactada en el contrato.
     *
     * @return array{days: int, pension_base: int, afc_base: int}
     */
    public function employerCoverage(Employee $employee, CarbonInterface $periodStart, CarbonInterface $periodEnd, float $pensionCap, float $afcCap): array
    {
        $start = CarbonImmutable::parse($periodStart->toDateString());
        $end = CarbonImmutable::parse($periodEnd->toDateString());

        $leaves = MedicalLeave::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [MedicalLeave::STATUS_IN_PROCESS, MedicalLeave::STATUS_AUTHORIZED, MedicalLeave::STATUS_REDUCED])
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->orderBy('start_date')
            ->get();

        $spans = [];
        foreach ($leaves as $leave) {
            $leaveStart = CarbonImmutable::parse($leave->start_date->toDateString());
            $leaveEnd = $leave->effectiveEndDate();
            if ($leaveEnd === null) {
                continue;
            }
            $from = $leaveStart->lt($start) ? $start : $leaveStart;
            $to = $leaveEnd->gt($end) ? $end : $leaveEnd;
            if ($from->lte($to)) {
                $spans[] = ['days' => (int) $from->diffInDays($to) + 1, 'salary' => $this->taxableBefore($employee, $this->root($leave))];
            }
        }

        $calendarDays = array_sum(array_column($spans, 'days'));
        if ($calendarDays === 0) {
            return ['days' => 0, 'pension_base' => 0, 'afc_base' => 0];
        }

        // Mes completo con licencia: 30 días, tenga el mes 28 o 31.
        $monthDays = (int) $start->diffInDays($end) + 1;
        $days = $calendarDays >= $monthDays ? 30 : min(30, $calendarDays);
        $scale = $days / $calendarDays;

        $pension = 0.0;
        $afc = 0.0;
        foreach ($spans as $span) {
            $fraction = $span['days'] * $scale / 30;
            $pension += ($pensionCap > 0 ? min($span['salary'], $pensionCap) : $span['salary']) * $fraction;
            $afc += ($afcCap > 0 ? min($span['salary'], $afcCap) : $span['salary']) * $fraction;
        }

        return ['days' => $days, 'pension_base' => (int) round($pension), 'afc_base' => (int) round($afc)];
    }

    /**
     * Resumen para la ficha: días y subsidio estimado del año calendario indicado.
     *
     * @return array{days: int, leaves: int, in_process: int, subsidy: float}
     */
    public function yearSummary(Collection $leaves, int $year): array
    {
        $ofYear = $leaves->filter(fn (MedicalLeave $leave) => $leave->status !== MedicalLeave::STATUS_CANCELLED
            && $leave->start_date->year === $year);

        return [
            'days' => (int) $ofYear->sum(fn (MedicalLeave $leave) => $leave->effective_days),
            'leaves' => $ofYear->count(),
            'in_process' => $leaves->where('status', MedicalLeave::STATUS_IN_PROCESS)->count(),
            'subsidy' => (float) $ofYear->sum(fn (MedicalLeave $leave) => (float) $leave->subsidy_amount),
        ];
    }

    /** Primera licencia de la cadena de continuaciones. */
    private function root(MedicalLeave $leave): MedicalLeave
    {
        $seen = [];
        while ($leave->continues_leave_id && ! isset($seen[$leave->id])) {
            $seen[$leave->id] = true;
            $previous = MedicalLeave::withoutGlobalScopes()->find($leave->continues_leave_id);
            if (! $previous || $previous->status === MedicalLeave::STATUS_CANCELLED) {
                break;
            }
            $leave = $previous;
        }

        return $leave;
    }

    /**
     * Reparte la carencia y recalcula los días y el monto de subsidio de toda la cadena.
     */
    private function recalculateChain(MedicalLeave $leave): void
    {
        $root = $this->root($leave)->refresh();
        $chain = [$root];
        while ($next = MedicalLeave::withoutGlobalScopes()
            ->where('continues_leave_id', end($chain)->id)
            ->where('status', '!=', MedicalLeave::STATUS_CANCELLED)
            ->first()) {
            $chain[] = $next;
        }

        $total = array_sum(array_map(fn (MedicalLeave $item) => $item->effective_days, $chain));
        $waiting = in_array($root->type, self::WAITING_PERIOD_TYPES, true) && $total <= self::WAITING_PERIOD_MAX_LEAVE_DAYS
            ? self::WAITING_PERIOD_DAYS
            : 0;

        foreach ($chain as $item) {
            $absorbed = min($waiting, $item->effective_days);
            $waiting -= $absorbed;
            $subsidyDays = $item->effective_days - $absorbed;

            $item->update([
                'subsidy_base_amount' => $root->subsidy_base_amount,
                'subsidy_daily_amount' => $root->subsidy_daily_amount,
                'subsidy_detail' => $root->subsidy_detail,
                'subsidy_days' => $subsidyDays,
                'subsidy_amount' => $root->subsidy_daily_amount !== null
                    ? round($root->subsidy_daily_amount * $subsidyDays)
                    : null,
            ]);
        }
    }

    /** Renta imponible del mes anterior al inicio de la licencia; sin liquidación, la del contrato. */
    private function taxableBefore(Employee $employee, MedicalLeave $root): float
    {
        $month = CarbonImmutable::parse($root->start_date->toDateString())->startOfMonth()->subMonth();
        $taxable = (float) Payroll::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('period_year', $month->year)
            ->where('period_month', $month->month)
            ->value('gross_salary');

        if ($taxable > 0) {
            return $taxable;
        }

        $terms = EmploymentContract::inForceFor($employee->id, $root->start_date);

        return (float) ($terms && (float) $terms->base_salary > 0 ? $terms->base_salary : $employee->salary);
    }

    /**
     * Entidad que paga el subsidio según el origen de la licencia y la afiliación del trabajador.
     *
     * @return array{0: string, 1: ?string}
     */
    private function payerFor(Employee $employee, string $type): array
    {
        $company = Company::withoutGlobalScopes()->with(['mutual', 'ccaf'])->find($employee->company_id);

        if (in_array($type, MedicalLeave::WORK_RELATED_TYPES, true)) {
            return $company?->mutual
                ? ['mutual', $company->mutual->nombre]
                : ['isl', 'Instituto de Seguridad Laboral (ISL)'];
        }

        $employee->loadMissing('isapre');
        if ($employee->health_system === 'isapre' || ($employee->health_system === null && $employee->isapre_id !== null)) {
            return ['isapre', $employee->isapre?->nombre ? 'Isapre '.$employee->isapre->nombre : 'Isapre'];
        }

        // Afiliados a Fonasa: si la empresa está adherida a una caja, ésta paga el subsidio.
        return $company?->ccaf
            ? ['ccaf', $company->ccaf->nombre]
            : ['compin', 'COMPIN / Fonasa'];
    }

    private function markAttendance(Employee $employee, CarbonImmutable $start, CarbonImmutable $end, string $folio): void
    {
        $existing = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', '>=', $start->toDateString())
            ->where('date', '<', $end->addDay()->toDateString())
            ->whereIn('status', ['vacaciones', 'asistio', 'atraso'])
            ->orderBy('date')
            ->first();

        if ($existing) {
            throw ValidationException::withMessages(['start_date' => [$existing->status === 'vacaciones'
                ? sprintf(
                    'El %s está registrado como vacaciones. La licencia suspende el feriado: anula o ajusta esas vacaciones antes de registrarla.',
                    $existing->date->format('d/m/Y')
                )
                : sprintf(
                    'El %s tiene una marca de asistencia (%s). Corrígela antes de registrar la licencia.',
                    $existing->date->format('d/m/Y'),
                    AttendanceRecord::$statusLabels[$existing->status] ?? $existing->status
                )]]);
        }

        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $this->attendance->storeOrUpdateAttendance($employee, [
                'date' => $day->toDateString(),
                'status' => 'licencia',
                'is_justified' => true,
                'justification_type' => 'licencia_medica',
                'justification_reason' => sprintf(self::ATTENDANCE_REASON, $folio),
            ]);
        }
    }

    /** La licencia se refleja en la asistencia; no se puede mover en meses ya cerrados. */
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
            throw ValidationException::withMessages(['start_date' => ['Las fechas de la licencia caen en un período de nómina cerrado o pagado.']]);
        }
    }

    private function log(MedicalLeave $leave, User $actor, string $description): void
    {
        EmployeeLog::create([
            'employee_id' => $leave->employee_id,
            'user_id' => $actor->id,
            'type' => EmployeeLog::TYPE_AUSENTISMO,
            'description' => $description,
        ]);
    }
}
