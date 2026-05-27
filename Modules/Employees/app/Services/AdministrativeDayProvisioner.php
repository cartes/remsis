<?php

namespace Modules\Employees\Services;

use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Companies\Models\CompanyAdministrativeDayPolicy;

class AdministrativeDayProvisioner
{
    protected AdministrativeDayBalanceService $balanceService;

    public function __construct(AdministrativeDayBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Batch provision balances for all active employees of a company in the current cycle.
     *
     * @param  Company  $company
     * @return int  Number of balances created
     */
    public function provisionCompany(Company $company): int
    {
        $policy = CompanyAdministrativeDayPolicy::where('company_id', $company->id)->first();
        if (!$policy || !$policy->is_enabled) {
            return 0;
        }

        // Definición de colaborador activo según la spec:
        // - pertenece a la empresa
        // - no está eliminado lógicamente
        // - y si tiene usuario asociado, user.status = true
        $employees = Employee::where('company_id', $company->id)
            ->where('status', 'active')
            ->whereHas('user', function ($query) {
                $query->where('status', true);
            })
            ->get();

        $count = 0;
        foreach ($employees as $employee) {
            $balance = $this->balanceService->getOrCreateBalance($employee, now());
            if ($balance && $balance->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Provision balance for a single new or reactivated employee.
     *
     * @param  Employee  $employee
     * @return void
     */
    public function provisionEmployee(Employee $employee): void
    {
        if ($employee->status === 'active' && $employee->user && $employee->user->status) {
            $this->balanceService->getOrCreateBalance($employee, now());
        }
    }
}
