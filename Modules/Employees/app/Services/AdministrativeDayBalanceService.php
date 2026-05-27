<?php

namespace Modules\Employees\Services;

use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeAdministrativeDayBalance;
use Modules\Companies\Models\CompanyAdministrativeDayPolicy;

class AdministrativeDayBalanceService
{
    protected AdministrativeDayCycleResolver $cycleResolver;

    public function __construct(AdministrativeDayCycleResolver $cycleResolver)
    {
        $this->cycleResolver = $cycleResolver;
    }

    /**
     * Get or dynamically initialize the balance for an employee on a given date.
     *
     * @param  Employee  $employee
     * @param  mixed     $date
     * @return EmployeeAdministrativeDayBalance|null
     */
    public function getOrCreateBalance(Employee $employee, $date): ?EmployeeAdministrativeDayBalance
    {
        $policy = CompanyAdministrativeDayPolicy::where('company_id', $employee->company_id)->first();
        
        if (!$policy || !$policy->is_enabled) {
            return null;
        }

        $resolved = $this->cycleResolver->resolve($date, $policy->cycle_type);

        return EmployeeAdministrativeDayBalance::firstOrCreate(
            [
                'company_id'  => $employee->company_id,
                'employee_id' => $employee->id,
                'cycle_key'   => $resolved['cycle_key'],
            ],
            [
                'policy_id'             => $policy->id,
                'cycle_starts_on'       => $resolved['starts_on']->format('Y-m-d'),
                'cycle_ends_on'         => $resolved['ends_on']->format('Y-m-d'),
                'policy_snapshot_days'  => $policy->days_per_cycle,
                'policy_snapshot_unit'  => $policy->unit,
                'policy_snapshot_cycle' => $policy->cycle_type,
                'assigned_units'        => $policy->days_per_cycle,
                'used_units'            => 0,
                'available_units'       => $policy->days_per_cycle,
            ]
        );
    }

    /**
     * Get the balance for a specific cycle key.
     *
     * @param  Employee  $employee
     * @param  string    $cycleKey
     * @return EmployeeAdministrativeDayBalance|null
     */
    public function getBalanceForCycle(Employee $employee, string $cycleKey): ?EmployeeAdministrativeDayBalance
    {
        return EmployeeAdministrativeDayBalance::where('employee_id', $employee->id)
            ->where('cycle_key', $cycleKey)
            ->first();
    }
}
