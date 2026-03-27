<?php

namespace Modules\Companies\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Users\Models\User;
use Throwable;

class EmployeeService
{
    /**
     * Crea un nuevo usuario y su registro de empleado asociado a una empresa.
     *
     * @param array $data
     * @param Company $company
     * @return Employee
     * @throws Throwable
     */
    public function createEmployee(array $data, Company $company): Employee
    {
        return DB::transaction(function () use ($data, $company) {
            // 1. Crear usuario
            $user = User::create([
                'name'     => trim($data['first_name'] . ' ' . $data['last_name']),
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'status'   => true,
            ]);

            $user->assignRole('employee');

            // 2. Crear registro de empleado con todos los campos
            return Employee::create([
                'user_id'             => $user->id,
                'company_id'          => $company->id,
                'first_name'          => $data['first_name'],
                'last_name'           => $data['last_name'],
                'email'               => $data['email'],
                'rut'                 => $data['rut'] ?? null,
                'birth_date'          => $data['birth_date'] ?? null,
                'gender'              => $data['gender'] ?? null,
                'phone'               => $data['phone'] ?? null,
                'address'             => $data['address'] ?? null,
                'nationality'         => $data['nationality'] ?? null,
                'position'            => $data['position'] ?? null,
                'hire_date'           => $data['hire_date'] ?? null,
                'contract_type'       => $data['contract_type'] ?? null,
                'work_schedule_type'  => $data['work_schedule_type'] ?? 'full_time',
                'cost_center_id'      => $data['cost_center_id'] ?? null,
                'afp_id'              => $data['afp_id'] ?? null,
                'health_system'       => $data['health_system'] ?? 'fonasa',
                'isapre_id'           => $data['isapre_id'] ?? null,
                'health_contribution' => $data['health_contribution'] ?? null,
                'ccaf_id'             => $data['ccaf_id'] ?? null,
                'apv_amount'          => $data['apv_amount'] ?? null,
                'salary'              => $data['salary'] ?? null,
                'salary_type'         => $data['salary_type'] ?? 'mensual',
                'num_dependents'      => $data['num_dependents'] ?? 0,
                'bank_id'             => $data['bank_id'] ?? null,
                'bank_account_type'   => $data['bank_account_type'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'payment_method'      => $data['payment_method'] ?? 'efectivo',
                'is_in_payroll'       => true,
                'status'              => 'active',
            ]);
        });
    }

    /**
     * Actualiza los datos de un empleado y su usuario asociado.
     *
     * @param Employee $employee
     * @param array $validatedData
     * @param string|null $newProfilePhotoPath
     * @return void
     * @throws Throwable
     */
    public function updateEmployee(Employee $employee, array $validatedData, ?string $newProfilePhotoPath): void
    {
        $user = $employee->user()->firstOrFail();

        DB::transaction(function () use ($employee, $user, $validatedData, $newProfilePhotoPath) {
            $employeeData = $validatedData;
            if (isset($employeeData['part_time_schedule']) && is_string($employeeData['part_time_schedule'])) {
                $employeeData['part_time_schedule'] = json_decode($employeeData['part_time_schedule'], true);
            }
            unset($employeeData['profile_photo']);

            $employee->update($employeeData);

            $changed = false;

            if (!empty($validatedData['first_name']) && !empty($validatedData['last_name'])) {
                $newName = trim("{$validatedData['first_name']} {$validatedData['last_name']}");

                if ($user->name !== $newName) {
                    $user->name = $newName;
                    $changed = true;
                }
            }

            if (!empty($validatedData['email']) && $user->email !== $validatedData['email']) {
                $user->email = $validatedData['email'];
                $changed = true;
            }

            if ($newProfilePhotoPath !== null) {
                $user->profile_photo = $newProfilePhotoPath;
                $changed = true;
            }

            if ($changed) {
                $user->save();
            }
        });
    }
}
