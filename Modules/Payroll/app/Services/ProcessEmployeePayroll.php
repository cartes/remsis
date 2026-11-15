<?php

namespace Modules\Payroll\Services;

use Modules\Employees\Models\Employee;
use Modules\Payroll\DTOs\PayrollResultDTO;
use Modules\Payroll\Models\PayrollPeriod;

/**
 * Cálculo individual con salida en DTO. Delega en el motor único
 * (PayrollCalculationService) y sólo da formato al resultado.
 */
class ProcessEmployeePayroll
{
    public function __construct(private ?PayrollCalculationService $calculator = null)
    {
        $this->calculator ??= app(PayrollCalculationService::class);
    }

    public function execute(int $employeeId, int $periodId): PayrollResultDTO
    {
        $employee = Employee::findOrFail($employeeId);
        $period = PayrollPeriod::findOrFail($periodId);

        $payroll = $this->calculator->calculateEmployee($employee, $period);
        $details = $payroll->details()->orderBy('id')->get();

        $describe = fn ($type) => $details->where('type', $type)
            ->map(fn ($detail) => [
                'concept' => $detail->description ?: $detail->concept,
                'amount' => (float) $detail->amount,
                'type' => $detail->concept,
            ])
            ->values()
            ->all();

        return new PayrollResultDTO(
            $employee->id,
            $period->id,
            (float) $payroll->base_salary,
            (float) $payroll->worked_days,
            (float) $details->where('concept', 'SUELDO_BASE')->sum('amount'),
            (float) $payroll->gratification_amount,
            (float) $payroll->gross_salary,
            (float) $payroll->non_taxable_earnings,
            (float) $payroll->total_earnings,
            $describe('earning'),
            (float) $payroll->afp_amount,
            (float) $payroll->isapre_amount,
            (float) $payroll->cesantia_amount,
            (float) $payroll->impuesto_unico_amount,
            (float) $payroll->total_deductions,
            $describe('deduction'),
            (float) $payroll->net_salary,
        );
    }
}
