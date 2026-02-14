<?php

namespace Modules\Payroll\Services;

use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollLine;
use Modules\Employees\Models\Employee;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Modules\AdminPanel\Models\LegalParameter;

class PayrollCalculationService
{
    /**
     * Calculate payroll for all active employees in the given period.
     *
     * @param PayrollPeriod $period
     * @return int Number of payroll lines created/updated
     */
    public function calculatePeriod(PayrollPeriod $period): int
    {
        // ... (lines 20-41 remain unchanged, just ensuring context)
        // 1. Get active employees for the company
        // You might want to filter by hire_date <= period_end_date and termination_date >= period_start_date
        $employees = Employee::where('company_id', $period->company_id)
            ->where('status', 'active') // Assuming 'active' is the status
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
     *
     * @param Employee $employee
     * @param PayrollPeriod $period
     * @return PayrollLine
     */
    public function calculateEmployee(Employee $employee, PayrollPeriod $period): PayrollLine
    {
        $company = $period->company;
        
        // 1. Sueldo Base
        $haberBase = $employee->salary ?? 0;
        
        // 2. Horas Extras
        // Check if company allows overtime
        $overtimeAmount = 0;
        $overtimeHours = 0;

        // If updating an existing line, preserve entered overtime values
        $existingLine = PayrollLine::where('employee_id', $employee->id)
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
             if($company->weekly_hours == 44) $factor = 0.0079545;
             
             // If manual amount was set? For now auto-calc from hours
             $overtimeAmount = round($haberBase * $factor * $overtimeHours);
        }

        // 3. Gratificación
        // Default to existing value (allows manual edit for 'convencional' or others)
        $gratificationAmount = ($existingLine) ? $existingLine->gratification_amount : 0;

        if ($company->gratification_system === 'art_50') {
            // 25% del sueldo base con tope de 4.75 IMM anual (prorrateado mensual)
            // IMM dinámico desde parámetros legales
             $immParam = LegalParameter::where('key', 'monthly_minimum_wage')->value('value');
             $imm = (is_numeric($immParam) && $immParam > 0) ? $immParam : 500000;
             $topeAnual = 4.75 * $imm;
             $topeMensual = $topeAnual / 12;
             
             $calc25 = ($haberBase + $overtimeAmount) * 0.25;
             $gratificationAmount = round(min($calc25, $topeMensual));
             
        } elseif ($company->gratification_system === 'sin_gratificacion') {
             $gratificationAmount = 0;
        }
        // For 'art_47' and 'convencional', we keep the manual/existing value.

        // 4. Total Imponible
        $imponible = $haberBase + $overtimeAmount + $gratificationAmount;

        // 5. Deducciones Legales
        // afp: imponible * 0.10 (Simulado)
        $afpAmount = round($imponible * 0.10);

        // salud: imponible * 0.07
        $saludAmount = round($imponible * 0.07);

        // cesantia: imponible * 0.006 (0.6%)
        $cesantiaAmount = round($imponible * 0.006);

        // total_deductions
        $totalDeductions = $afpAmount + $saludAmount + $cesantiaAmount;

        // total_neto: imponible - deducciones
        $totalNeto = $imponible - $totalDeductions;

        // Create or Update PayrollLine
        return PayrollLine::updateOrCreate(
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
                
                'base_salary' => $haberBase,
                'gratification_amount' => $gratificationAmount,
                'gross_salary' => $imponible,
                
                'afp_id' => $employee->afp_id,
                'afp_amount' => $afpAmount,
                
                'isapre_id' => $employee->isapre_id,
                'isapre_amount' => $saludAmount,
                
                'ccaf_id' => $employee->ccaf_id,
                'ccaf_amount' => 0,
                
                'cesantia_amount' => $cesantiaAmount,
                'impuesto_unico_amount' => 0,
                'anticipos_amount' => 0,
                'otros_descuentos' => 0,
                
                'total_deductions' => $totalDeductions,
                'net_salary' => $totalNeto,
                
                'status' => 'pending', 
            ]
        );
    }
}
