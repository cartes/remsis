<?php

declare(strict_types=1);

namespace Modules\Employees\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Models\AdministrativeDayUsage;
use Modules\Employees\Models\EmployeeAdministrativeDayBalance;

use Modules\Employees\Models\AdministrativeDayRequest;
use Modules\Employees\Models\EmployeeLog;
use Modules\Users\Models\User;

class AdministrativeDayAttendanceService
{
    protected AdministrativeDayBalanceService $balanceService;

    public function __construct(AdministrativeDayBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Store or update an attendance record and adjust administrative day balances/usages.
     *
     * @param Employee $employee
     * @param array $data
     * @return AttendanceRecord
     * @throws ValidationException
     */
    public function storeOrUpdateAttendance(Employee $employee, array $data): AttendanceRecord
    {
        $date = $data['date'];
        $newStatus = $data['status'];
        $isJustified = filter_var($data['is_justified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $newJustificationType = $data['justification_type'] ?? null;
        $justificationReason = $data['justification_reason'] ?? null;

        // Clean up justification if not absent/licencia
        if (!in_array($newStatus, ['ausente', 'licencia'], true)) {
            $isJustified = false;
            $newJustificationType = null;
            $justificationReason = null;
        }

        return DB::transaction(function () use ($employee, $date, $newStatus, $isJustified, $newJustificationType, $justificationReason) {
            // Find existing record
            $record = AttendanceRecord::where('employee_id', $employee->id)
                ->where('date', $date)
                ->first();

            $oldJustificationType = $record ? $record->justification_type : null;

            $wasAdminDay = ($oldJustificationType === 'administrative_day');
            $isAdminDay = ($newJustificationType === 'administrative_day');

            // Case 1: Was not administrative day, and is not administrative day
            if (!$wasAdminDay && !$isAdminDay) {
                return AttendanceRecord::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date],
                    [
                        'status' => $newStatus,
                        'is_justified' => $isJustified,
                        'justification_type' => $newJustificationType,
                        'justification_reason' => $justificationReason,
                    ]
                );
            }

            // Get or create balance for the date
            $balance = $this->balanceService->getOrCreateBalance($employee, $date);

            // Case 2: Changing from non-administrative day to administrative day
            if (!$wasAdminDay && $isAdminDay) {
                if (!$balance) {
                    throw ValidationException::withMessages([
                        'justification_type' => ['La política de días administrativos no está activa para esta empresa.'],
                    ]);
                }

                // Lock the balance row for update to prevent race conditions
                $lockedBalance = EmployeeAdministrativeDayBalance::where('id', $balance->id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedBalance->available_units < 1) {
                    throw ValidationException::withMessages([
                        'justification_type' => [
                            sprintf(
                                'El colaborador no tiene días administrativos disponibles en el ciclo actual (Saldo disponible: %s).',
                                number_format((float) $lockedBalance->available_units, 1)
                            )
                        ],
                    ]);
                }

                // Create attendance record
                $record = AttendanceRecord::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date],
                    [
                        'status' => $newStatus,
                        'is_justified' => $isJustified,
                        'justification_type' => $newJustificationType,
                        'justification_reason' => $justificationReason,
                    ]
                );

                // Create usage
                AdministrativeDayUsage::create([
                    'company_id' => $employee->company_id,
                    'employee_id' => $employee->id,
                    'attendance_record_id' => $record->id,
                    'balance_id' => $lockedBalance->id,
                    'used_units' => 1.0,
                    'used_on' => $date,
                    'notes' => $justificationReason,
                ]);

                // Update balance units
                $lockedBalance->used_units += 1.0;
                $lockedBalance->available_units -= 1.0;
                $lockedBalance->save();

                return $record;
            }

            // Case 3: Changing from administrative day to non-administrative day
            if ($wasAdminDay && !$isAdminDay) {
                // Lock the balance row
                $lockedBalance = EmployeeAdministrativeDayBalance::where('id', $balance->id)
                    ->lockForUpdate()
                    ->first();

                // Find and delete the usage
                AdministrativeDayUsage::where('attendance_record_id', $record->id)->delete();

                // Refund the balance
                $lockedBalance->used_units = max(0.0, $lockedBalance->used_units - 1.0);
                $lockedBalance->available_units = min($lockedBalance->assigned_units, $lockedBalance->available_units + 1.0);
                $lockedBalance->save();

                // Update attendance record
                $record->update([
                    'status' => $newStatus,
                    'is_justified' => $isJustified,
                    'justification_type' => $newJustificationType,
                    'justification_reason' => $justificationReason,
                ]);

                return $record;
            }

            // Case 4: Was administrative day, and remains administrative day (e.g. updating description or status notes)
            $record->update([
                'status' => $newStatus,
                'is_justified' => $isJustified,
                'justification_type' => $newJustificationType,
                'justification_reason' => $justificationReason,
            ]);

            // Update usage notes
            AdministrativeDayUsage::where('attendance_record_id', $record->id)->update([
                'notes' => $justificationReason,
            ]);

            return $record;
        });
    }

    /**
     * Delete an attendance record and revert the administrative day balance/usage if needed.
     *
     * @param Employee $employee
     * @param AttendanceRecord $record
     * @return void
     */
    public function deleteAttendance(Employee $employee, AttendanceRecord $record): void
    {
        DB::transaction(function () use ($employee, $record) {
            if ($record->justification_type === 'administrative_day') {
                $balance = $this->balanceService->getOrCreateBalance($employee, $record->date);

                if ($balance) {
                    $lockedBalance = EmployeeAdministrativeDayBalance::where('id', $balance->id)
                        ->lockForUpdate()
                        ->first();

                    // Find and delete usage
                    AdministrativeDayUsage::where('attendance_record_id', $record->id)->delete();

                    // Revert balance
                    $lockedBalance->used_units = max(0.0, $lockedBalance->used_units - 1.0);
                    $lockedBalance->available_units = min($lockedBalance->assigned_units, $lockedBalance->available_units + 1.0);
                    $lockedBalance->save();
                }
            }

            $record->delete();
        });
    }

    /**
     * Approve an administrative day request.
     *
     * @param AdministrativeDayRequest $request
     * @param User $resolver
     * @return void
     * @throws ValidationException
     */
    public function approveRequest(AdministrativeDayRequest $request, User $resolver): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Esta solicitud ya no se encuentra pendiente.'],
            ]);
        }

        $employee = $request->employee;
        $date = $request->requested_date->format('Y-m-d');

        // Check if there is already an attendance record for this date
        $exists = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        if ($exists && $exists->justification_type === 'administrative_day') {
            throw ValidationException::withMessages([
                'requested_date' => ['El colaborador ya tiene un día administrativo registrado en esta fecha.'],
            ]);
        }

        // Verify if payroll period is locked/closed
        $isPeriodLocked = \Modules\Payroll\Models\PayrollPeriod::where('company_id', $request->company_id)
            ->whereIn('status', [\Modules\Payroll\Models\PayrollPeriod::STATUS_CLOSED, \Modules\Payroll\Models\PayrollPeriod::STATUS_PAID])
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        if ($isPeriodLocked) {
            throw ValidationException::withMessages([
                'requested_date' => ['No se pueden aprobar solicitudes para fechas en períodos de nómina cerrados o pagados.'],
            ]);
        }

        DB::transaction(function () use ($request, $employee, $date, $exists, $resolver) {
            $balance = $this->balanceService->getOrCreateBalance($employee, $date);

            if (!$balance) {
                throw ValidationException::withMessages([
                    'balance' => ['La política de días administrativos no está activa para esta empresa.'],
                ]);
            }

            // Lock the balance row
            $lockedBalance = EmployeeAdministrativeDayBalance::where('id', $balance->id)
                ->lockForUpdate()
                ->first();

            if ($lockedBalance->available_units < 1) {
                throw ValidationException::withMessages([
                    'balance' => ['El colaborador no tiene días administrativos disponibles en el ciclo actual.'],
                ]);
            }

            // Update/create attendance record
            $attendance = AttendanceRecord::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $date],
                [
                    'status' => 'ausente',
                    'is_justified' => true,
                    'justification_type' => 'administrative_day',
                    'justification_reason' => $request->notes,
                ]
            );

            // Create usage linked to request
            AdministrativeDayUsage::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'attendance_record_id' => $attendance->id,
                'balance_id' => $lockedBalance->id,
                'request_id' => $request->id,
                'used_units' => 1.0,
                'used_on' => $date,
                'notes' => $request->notes,
            ]);

            // Deduct balance
            $lockedBalance->used_units += 1.0;
            $lockedBalance->available_units -= 1.0;
            $lockedBalance->save();

            // Update request
            $request->update([
                'status' => 'approved',
                'resolved_by' => $resolver->id,
                'resolved_at' => now(),
            ]);

            // Log event in employee logs
            EmployeeLog::create([
                'employee_id' => $employee->id,
                'user_id' => $resolver->id,
                'type' => EmployeeLog::TYPE_AUSENTISMO,
                'description' => sprintf(
                    'Día administrativo aprobado para la fecha %s (Autorizado por %s).',
                    $request->requested_date->format('d/m/Y'),
                    $resolver->name
                ),
            ]);
        });
    }

    /**
     * Reject an administrative day request.
     *
     * @param AdministrativeDayRequest $request
     * @param User $resolver
     * @param string $reason
     * @return void
     * @throws ValidationException
     */
    public function rejectRequest(AdministrativeDayRequest $request, User $resolver, string $reason): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Esta solicitud ya no se encuentra pendiente.'],
            ]);
        }

        $request->update([
            'status' => 'rejected',
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Log event in employee logs
        EmployeeLog::create([
            'employee_id' => $request->employee_id,
            'user_id' => $resolver->id,
            'type' => EmployeeLog::TYPE_AUSENTISMO,
            'description' => sprintf(
                'Día administrativo rechazado para la fecha %s (Rechazado por %s). Motivo: %s',
                $request->requested_date->format('d/m/Y'),
                $resolver->name,
                $reason
            ),
        ]);
    }
}
