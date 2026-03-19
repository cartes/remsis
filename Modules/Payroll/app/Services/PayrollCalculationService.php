<?php

namespace Modules\Payroll\Services;

use Illuminate\Support\Facades\DB;
use Modules\AdminPanel\Models\LegalParameter;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;

class PayrollCalculationService
{
    /**
     * Calculate payroll for all active employees in the given period.
     *
     * @return int Number of payroll lines created/updated
     */
    public function calculatePeriod(PayrollPeriod $period): int
    {
        // ... (lines 20-41 remain unchanged, just ensuring context)
        // 1. Get active employees for the company
        // You might want to filter by hire_date <= period_end_date and termination_date >= period_start_date
        /** @var \Illuminate\Database\Eloquent\Collection<int, Employee> $employees */
        $employees = Employee::where('company_id', $period->company_id)
            ->where('status', 'active') // Assuming 'active' is the status
            ->where('is_in_payroll', true)
            ->get();

        $count = 0;

        DB::transaction(function () use ($employees, $period, &$count) {
            foreach ($employees as $employee) {
                $this->calculateEmployee($employee, $period);
                $count++;
            }

            // 4. Change status to 'calculated'
            $period->status = 'calculated';
            $period->save();
        });

        return $count;
    }

    /**
     * Calculate payroll for a single employee.
     */
    public function calculateEmployee(Employee $employee, PayrollPeriod $period): Payroll
    {
        $company = $period->company;

        // 1. Sueldo Base
        $haberBase = $employee->salary ?? 0;

        // 2. Horas Extras
        // Check if company allows overtime
        $overtimeAmount = 0;
        $overtimeHours = 0;

        // If updating an existing line, preserve entered overtime values
        $existingLine = Payroll::where('employee_id', $employee->id)
            ->where('payroll_period_id', $period->id)
            ->first();

        if ($company->allows_overtime && $existingLine) {
            $overtimeHours = $existingLine->overtime_hours;
            // Calculate overtime amount: (Sueldo Base / 30 * 28 / 180) * 1.5 * hours (Standard formula, simplified here)
            // Formula standard 45hrs: (Sueldo / 30 * 7 / 45 * 1.5) ??
            // Let's use simplified: (Base / 30 / 8) * 1.5 * hours for now or specific factor
            // Factor for 44 hours = 0.0079545
            // Factor for 45 hours = 0.0077777
            $factor = 0.0077777; // Asumimos 45 hrs por defecto o parametrizable
            if ($company->weekly_hours == 44) {
                $factor = 0.0079545;
            }

            // If manual amount was set? For now auto-calc from hours
            $overtimeAmount = (int) round($haberBase * $factor * $overtimeHours);
        }

        // 3. Gratificación
        // Default to existing value (allows manual edit for 'convencional' or others)
        $comisionesAmount = ($existingLine) ? (int) $existingLine->comisiones_amount : 0;
        $semanaCorridaAmount = ($existingLine) ? (int) $existingLine->semana_corrida_amount : 0;
        $bonosAmount = ($existingLine) ? (int) $existingLine->bonos_imponibles_amount : 0;
        $aguinaldosAmount = ($existingLine) ? (int) $existingLine->aguinaldos_amount : 0;

        $gratificationAmount = ($existingLine) ? (int) $existingLine->gratification_amount : 0;
        $anticiposAmount = ($existingLine) ? (int) $existingLine->anticipos_amount : 0;
        $otrosDescuentos = ($existingLine) ? (int) $existingLine->otros_descuentos : 0;

        // Total base for gratification
        $baseGratificacion = $haberBase + $overtimeAmount + $comisionesAmount + $semanaCorridaAmount + $bonosAmount + $aguinaldosAmount;

        if ($company->gratification_system === 'art_50') {
            // 25% del sueldo base y otros imponibles con tope de 4.75 IMM anual (prorrateado mensual)
            // IMM dinámico desde parámetros legales
            $immParam = LegalParameter::where('key', 'monthly_minimum_wage')->value('value');
            $imm = (is_numeric($immParam) && $immParam > 0) ? $immParam : 500000;
            $topeAnual = 4.75 * $imm;
            $topeMensual = $topeAnual / 12;

            $calc25 = $baseGratificacion * 0.25;
            $gratificationAmount = (int) round(min($calc25, $topeMensual));

        } elseif ($company->gratification_system === 'sin_gratificacion') {
            $gratificationAmount = 0;
        }
        // For 'art_47' and 'convencional', we keep the manual/existing value.

        // 4. Total Imponible
        $imponible = (int) ($baseGratificacion + $gratificationAmount);

        // 5. Haberes No Imponibles
        $mealAllowance = (int) ($employee->meal_allowance ?? 0);
        $mobilityAllowance = (int) ($employee->mobility_allowance ?? 0);
        $nonTaxableEarnings = $mealAllowance + $mobilityAllowance;
        $totalEarnings = $imponible + $nonTaxableEarnings;

        // 6. Deducciones Legales
        // afp: imponible * 0.10 (Simulado)
        $afpAmount = (int) round($imponible * 0.10);
        // salud: imponible * 0.07
        $saludAmount = (int) round($imponible * 0.07);
        // cesantia: imponible * 0.006 (0.6%)
        $cesantiaAmount = (int) round($imponible * 0.006);

        // total_deductions
        $totalDeductions = (int) ($afpAmount + $saludAmount + $cesantiaAmount + $anticiposAmount + $otrosDescuentos);

        // total_neto: total earnings (imponible + no imponible) - deducciones
        $totalNeto = (int) ($totalEarnings - $totalDeductions);

        // Create or update the canonical payroll row for this employee and period.
        return Payroll::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
            ],
            [
                'user_id' => $employee->user_id,
                'company_id' => $period->company_id,
                'period_year' => $period->year,
                'period_month' => $period->month,
                'worked_days' => 30,

                'overtime_hours' => $overtimeHours,
                'overtime_amount' => $overtimeAmount,
                'comisiones_amount' => $comisionesAmount,
                'semana_corrida_amount' => $semanaCorridaAmount,
                'bonos_imponibles_amount' => $bonosAmount,
                'aguinaldos_amount' => $aguinaldosAmount,

                'base_salary' => $haberBase,
                'gratification_amount' => $gratificationAmount,
                'gross_salary' => $imponible,
                'meal_allowance' => $mealAllowance,
                'mobility_allowance' => $mobilityAllowance,
                'non_taxable_earnings' => $nonTaxableEarnings,
                'total_earnings' => $totalEarnings,

                'afp_id' => $employee->afp_id,
                'afp_amount' => $afpAmount,

                'isapre_id' => $employee->isapre_id,
                'isapre_amount' => $saludAmount,

                'ccaf_id' => $employee->ccaf_id,
                'ccaf_amount' => 0,

                'cesantia_amount' => $cesantiaAmount,
                'impuesto_unico_amount' => 0,
                'anticipos_amount' => $anticiposAmount,
                'otros_descuentos' => $otrosDescuentos,

                'total_deductions' => $totalDeductions,
                'net_salary' => $totalNeto,

                'status' => 'pending',
            ]
        );
    }
}
