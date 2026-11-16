<?php

declare(strict_types=1);

namespace Modules\Employees\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeLog;
use Modules\Employees\Models\EmploymentContract;
use Modules\Users\Models\User;

/**
 * Historial de condiciones contractuales. Un anexo (Art. 11 CT) no reescribe el contrato:
 * cierra la versión vigente el día anterior y abre otra desde la fecha de vigencia, de modo
 * que cada mes se liquide con el sueldo y las condiciones que regían entonces.
 */
class EmploymentContractService
{
    /** Condiciones que un anexo puede modificar. */
    private const TERMS = [
        'base_salary', 'position', 'contract_type', 'end_date', 'work_schedule_type', 'weekly_hours', 'cost_center_id',
    ];

    /**
     * @param  array<string, mixed>  $data  effective_date, change_reason y las condiciones que cambian
     */
    public function registerAmendment(Employee $employee, array $data, ?User $actor): EmploymentContract
    {
        $effective = CarbonImmutable::parse($data['effective_date']);

        return DB::transaction(function () use ($employee, $data, $effective, $actor) {
            Employee::withoutGlobalScopes()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $versions = fn () => EmploymentContract::query()->withoutGlobalScopes()->whereNull('deleted_at')
                ->where('employee_id', $employee->id);

            if ($versions()->where('valid_from', '>=', $effective->toDateString())->exists()) {
                throw ValidationException::withMessages(['effective_date' => [
                    'Ya existe una versión del contrato con vigencia desde esa fecha o una posterior.',
                ]]);
            }

            $current = EmploymentContract::inForceFor($employee->id, $effective)
                ?? $employee->syncActiveContract();

            if ($current === null) {
                throw ValidationException::withMessages(['effective_date' => ['El colaborador no tiene un contrato que modificar.']]);
            }

            if ($current->valid_from && $effective->lte($current->valid_from)) {
                throw ValidationException::withMessages(['effective_date' => [
                    'La vigencia del anexo debe ser posterior al inicio de la versión que modifica.',
                ]]);
            }

            $terms = array_intersect_key($data, array_flip(self::TERMS));
            $changed = collect($terms)->filter(fn ($value, $key) => (string) $value !== (string) ($current->{$key} instanceof \DateTimeInterface
                ? $current->{$key}->format('Y-m-d')
                : $current->{$key}));

            if ($changed->isEmpty()) {
                throw ValidationException::withMessages(['base_salary' => ['El anexo no modifica ninguna condición del contrato.']]);
            }

            $current->update(['valid_until' => $effective->subDay()->toDateString()]);

            $amendment = $current->replicate(['valid_until', 'termination_date', 'termination_cause_article']);
            $amendment->fill($terms + [
                'valid_from' => $effective->toDateString(),
                'valid_until' => null,
                'version_type' => EmploymentContract::VERSION_AMENDMENT,
                'change_reason' => $data['change_reason'] ?? null,
                'status' => EmploymentContract::STATUS_ACTIVE,
            ]);
            $amendment->save();

            EmployeeLog::create([
                'employee_id' => $employee->id,
                'user_id' => $actor?->id,
                'type' => EmployeeLog::TYPE_CONTRATO,
                'description' => sprintf('Anexo de contrato con vigencia desde el %s.', $effective->format('d/m/Y')),
                'old_values' => $changed->keys()->mapWithKeys(fn ($key) => [$key => $current->{$key} instanceof \DateTimeInterface
                    ? $current->{$key}->format('Y-m-d')
                    : $current->{$key}])->all(),
                'new_values' => $changed->all(),
            ]);

            // Un anexo que ya rige se refleja de inmediato en la ficha; uno futuro, al llegar su fecha.
            $this->applyVersionInForce($employee);

            return $amendment;
        });
    }

    /**
     * Copia a la ficha las condiciones de la versión que rige hoy. Devuelve true si la ficha cambió.
     */
    public function applyVersionInForce(Employee $employee): bool
    {
        $version = EmploymentContract::inForceFor($employee->id, now());

        // Sólo un anexo manda sobre la ficha: el contrato original se sincroniza en sentido contrario,
        // desde la ficha hacia el contrato.
        if ($version === null
            || $version->status !== EmploymentContract::STATUS_ACTIVE
            || $version->version_type !== EmploymentContract::VERSION_AMENDMENT) {
            return false;
        }

        $employee->fill([
            'salary' => (int) round((float) $version->base_salary),
            'position' => $version->position,
            'contract_type' => $version->contract_type,
            'work_schedule_type' => $version->work_schedule_type,
            'cost_center_id' => $version->cost_center_id,
        ]);
        if ($version->work_schedule_type === EmploymentContract::SCHEDULE_PART_TIME) {
            $employee->part_time_hours = $version->weekly_hours;
        }

        if (! $employee->isDirty()) {
            return false;
        }

        $employee->save();

        return true;
    }
}
