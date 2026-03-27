<?php

namespace Modules\Employees\Observers;

use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeLog;

class EmployeeObserver
{
    /**
     * Campos ignorados en la auditoría (meta-campos sin valor de negocio).
     */
    private array $ignoredFields = ['updated_at', 'created_at', 'remember_token'];

    /**
     * Campos cuyo cambio clasifica el evento como "remuneracion".
     */
    private array $remuneracionFields = [
        'salary', 'salary_type', 'apv_amount',
        'health_contribution', 'afp_id', 'isapre_id', 'health_system',
    ];

    /**
     * Campos cuyo cambio clasifica el evento como "contrato".
     */
    private array $contratoFields = [
        'contract_type', 'hire_date',
        'work_schedule', 'work_schedule_type', 'part_time_hours',
    ];

    // ──────────────────────────────────────────────────────────

    public function created(Employee $employee): void
    {
        EmployeeLog::create([
            'employee_id' => $employee->id,
            'user_id'     => auth()->id(),
            'type'        => EmployeeLog::TYPE_CREACION,
            'description' => 'Colaborador registrado en el sistema.',
            'old_values'  => null,
            'new_values'  => null,
        ]);
    }

    public function updated(Employee $employee): void
    {
        $changes = collect($employee->getChanges())
            ->except($this->ignoredFields)
            ->toArray();

        if (empty($changes)) {
            return;
        }

        // Captura los valores anteriores (getOriginal() aún tiene los datos pre-guardado
        // porque syncOriginal() se llama después del evento 'updated').
        $oldValues = collect($changes)
            ->mapWithKeys(fn ($_, $key) => [$key => $employee->getOriginal($key)])
            ->toArray();

        $type        = $this->determineType($changes);
        $description = $this->buildDescription($changes, $oldValues);

        EmployeeLog::create([
            'employee_id' => $employee->id,
            'user_id'     => auth()->id(),
            'type'        => $type,
            'description' => $description,
            'old_values'  => $this->formatValues($oldValues),
            'new_values'  => $this->formatValues($changes),
        ]);
    }

    // ── Helpers privados ──────────────────────────────────────

    private function determineType(array $changes): string
    {
        foreach ($this->remuneracionFields as $field) {
            if (array_key_exists($field, $changes)) {
                return EmployeeLog::TYPE_REMUNERACION;
            }
        }

        foreach ($this->contratoFields as $field) {
            if (array_key_exists($field, $changes)) {
                return EmployeeLog::TYPE_CONTRATO;
            }
        }

        return EmployeeLog::TYPE_AUDITORIA;
    }

    private function buildDescription(array $changes, array $oldValues): string
    {
        $labels = EmployeeLog::$fieldLabels;

        // Descripción especial para cambio de sueldo
        if (isset($changes['salary'])) {
            $old = '$' . number_format((float) ($oldValues['salary'] ?? 0), 0, ',', '.');
            $new = '$' . number_format((float) $changes['salary'], 0, ',', '.');

            return "Sueldo base modificado de {$old} a {$new}.";
        }

        // Descripción especial para cambio de tipo de contrato
        if (isset($changes['contract_type'])) {
            $old = $oldValues['contract_type'] ?? '—';
            $new = $changes['contract_type'] ?? '—';

            return "Tipo de contrato modificado de \"{$old}\" a \"{$new}\".";
        }

        // Descripción genérica para uno o varios campos
        $changedLabels = collect($changes)
            ->keys()
            ->map(fn ($f) => $labels[$f] ?? $f)
            ->join(', ');

        $count = count($changes);

        return $count === 1
            ? "Se modificó: {$changedLabels}."
            : "Se modificaron {$count} campo(s): {$changedLabels}.";
    }

    /**
     * Convierte los valores para almacenamiento: usa etiquetas legibles como claves.
     */
    private function formatValues(array $values): array
    {
        $labels = EmployeeLog::$fieldLabels;
        $result = [];

        foreach ($values as $field => $value) {
            $label = $labels[$field] ?? $field;
            $result[$label] = is_bool($value) ? ($value ? 'Sí' : 'No') : $value;
        }

        return $result;
    }
}
