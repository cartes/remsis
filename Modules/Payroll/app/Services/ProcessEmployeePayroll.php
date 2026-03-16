<?php

namespace Modules\Payroll\Services;

use Modules\AdminPanel\Models\LegalParameter;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollDetail;
use Modules\Payroll\DTOs\PayrollResultDTO;
use Illuminate\Support\Facades\DB;

class ProcessEmployeePayroll
{
    public function execute(int $employeeId, int $periodId): PayrollResultDTO
    {
        $employee = Employee::with(['afp', 'isapre'])->findOrFail($employeeId);
        $period = PayrollPeriod::findOrFail($periodId);

        // 1. Días Trabajados (Estándar 30 días para cálculo mensual)
        $workedDays = 30; // Podría ser dinámico basado en asistencia si se integra

        // 2. Cálculo de Haberes
        $baseSalary = (int)$employee->salary ?? 0;
        $proportionalSalary = (int)round(($baseSalary / 30) * $workedDays);
        
        // Gratificación Legal (Art. 50 del Código del Trabajo)
        // 25% del ingreso imponible con un tope de 4.75 ingresos mínimos mensuales (IMM) anuales
        $imm = LegalParameter::where('key', 'monthly_minimum_wage')->value('value') ?? 500000;
        $gratificationCap = (int)round((4.75 * $imm) / 12);
        $calculatedGratification = (int)round($proportionalSalary * 0.25);
        $gratification = min($calculatedGratification, $gratificationCap);

        $taxableEarnings = $proportionalSalary + $gratification;
        
        // Haberes No Imponibles
        $mealAllowance = (int)($employee->meal_allowance ?? 0);
        $mobilityAllowance = (int)($employee->mobility_allowance ?? 0);
        $nonTaxableEarnings = $mealAllowance + $mobilityAllowance;

        $totalEarnings = $taxableEarnings + $nonTaxableEarnings;

        // 3. Descuentos Legales
        // AFP
        $afpRate = $employee->afp?->rate ?? 10.0;
        $afpCommission = $employee->afp?->commission ?? 0.0;
        $afpTotalRate = ($afpRate + $afpCommission) / 100;
        
        // Tope Imponible AFP (80.2 UF para el año vigente)
        $ufValue = LegalParameter::where('key', 'uf_value')->value('value') ?? 37000;
        $taxableCap = 80.2 * $ufValue;
        $afpBase = min((float)$taxableEarnings, (float)$taxableCap);
        $afpAmount = (int)round($afpBase * $afpTotalRate);

        // Salud (7% obligatorio o plan de Isapre)
        $healthRate = 0.07;
        $healthBase = $afpBase; // El tope de salud es el mismo que el de la AFP
        $mandatoryHealth = (int)round($healthBase * $healthRate);
        
        // Si es Isapre, podría tener una cotización adicional o pactada
        $isapreAdditional = (int)($employee->health_contribution ?? 0); 
        $healthAmount = $mandatoryHealth + $isapreAdditional;

        // Seguro de Cesantía (AFC) - 0.6% para contratos indefinidos (cargo del trabajador)
        $afcAmount = 0;
        if ($employee->contract_type === 'indefinido') {
             // El tope de la AFC es mayor (120.3 UF)
             $afcCap = 120.3 * $ufValue;
             $afcBase = min((float)$taxableEarnings, (float)$afcCap);
             $afcAmount = (int)round($afcBase * 0.006);
        }

        // 4. Impuesto Único de Segunda Categoría (IUSC)
        $taxableForIUSC = $taxableEarnings - $afpAmount - $healthAmount - $afcAmount;
        $iuscAmount = (int)$this->calculateIUSC($taxableForIUSC);

        $totalDeductions = $afpAmount + $healthAmount + $afcAmount + $iuscAmount;
        $netSalary = $totalEarnings - $totalDeductions;

        // Desglose detallado para la salida JSON
        $earningsDetails = [
            ['concept' => 'Sueldo Base', 'amount' => $proportionalSalary, 'type' => 'taxable'],
            ['concept' => 'Gratificación Legal', 'amount' => $gratification, 'type' => 'taxable'],
            ['concept' => 'Asignación Colación', 'amount' => $mealAllowance, 'type' => 'non_taxable'],
            ['concept' => 'Asignación Movilización', 'amount' => $mobilityAllowance, 'type' => 'non_taxable'],
        ];

        $deductionsDetails = [
            ['concept' => 'Previsión AFP', 'amount' => $afpAmount, 'type' => 'legal'],
            ['concept' => 'Salud', 'amount' => $healthAmount, 'type' => 'legal'],
            ['concept' => 'Seguro Cesantía', 'amount' => $afcAmount, 'type' => 'legal'],
            ['concept' => 'Impuesto Único', 'amount' => $iuscAmount, 'type' => 'tax'],
        ];

        $result = new PayrollResultDTO(
            $employeeId,
            $periodId,
            $baseSalary,
            $workedDays,
            $proportionalSalary,
            $gratification,
            $taxableEarnings,
            $nonTaxableEarnings,
            $totalEarnings,
            $earningsDetails,
            $afpAmount,
            $healthAmount,
            $afcAmount,
            $iuscAmount,
            $totalDeductions,
            $deductionsDetails,
            $netSalary
        );

        // 5. Persistencia de Datos
        $this->persist($result, $employee, $period);

        return $result;
    }

    private function calculateIUSC(float $taxableBase): float
    {
        // Simplificación de la Tabla del SII (basada en UTM)
        $utm = LegalParameter::where('key', 'utm_value')->value('value') ?? 65000;
        $baseInUTM = $taxableBase / $utm;

        // Tramos de ejemplo (aproximados según tabla vigente)
        $tranches = [
            ['limit' => 13.5, 'factor' => 0, 'discount' => 0],
            ['limit' => 30, 'factor' => 0.04, 'discount' => 0.54],
            ['limit' => 50, 'factor' => 0.08, 'discount' => 1.74],
            ['limit' => 70, 'factor' => 0.135, 'discount' => 4.49],
            ['limit' => 90, 'factor' => 0.23, 'discount' => 11.14],
            ['limit' => 120, 'factor' => 0.304, 'discount' => 17.80],
            ['limit' => PHP_FLOAT_MAX, 'factor' => 0.35, 'discount' => 23.32],
        ];

        $applicableTranche = null;
        foreach ($tranches as $tranche) {
            if ($baseInUTM <= $tranche['limit']) {
                $applicableTranche = $tranche;
                break;
            }
        }

        if (!$applicableTranche || $applicableTranche['factor'] == 0) {
            return 0;
        }

        return (int)round(($taxableBase * $applicableTranche['factor']) - ($applicableTranche['discount'] * $utm));
    }

    private function persist(PayrollResultDTO $dto, Employee $employee, PayrollPeriod $period): void
    {
        DB::transaction(function () use ($dto, $employee, $period) {
            $payroll = Payroll::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'payroll_period_id' => $period->id,
                ],
                [
                    'user_id' => $employee->user_id,
                    'company_id' => $employee->company_id,
                    'period_year' => $period->year,
                    'period_month' => $period->month,
                    'worked_days' => $dto->worked_days,
                    'base_salary' => $dto->base_salary,
                    'gratification_amount' => $dto->gratification,
                    'gross_salary' => $dto->taxable_earnings,
                    'afp_id' => $employee->afp_id,
                    'afp_amount' => $dto->afp_amount,
                    'isapre_id' => $employee->isapre_id,
                    'isapre_amount' => $dto->health_amount,
                    'cesantia_amount' => $dto->afc_amount,
                    'impuesto_unico_amount' => $dto->iusc_amount,
                    'total_deductions' => $dto->total_deductions,
                    'net_salary' => $dto->net_salary,
                    'status' => 'processed',
                    'processed_at' => now(),
                ]
            );

            // Eliminar detalles antiguos y re-ingresarlos
            $payroll->details()->delete();

            foreach ($dto->earnings_details as $detail) {
                PayrollDetail::create([
                    'payroll_id' => $payroll->id,
                    'concept' => $detail['concept'],
                    'amount' => $detail['amount'],
                    'type' => 'earning',
                    'description' => $detail['type']
                ]);
            }

            foreach ($dto->deductions_details as $detail) {
                PayrollDetail::create([
                    'payroll_id' => $payroll->id,
                    'concept' => $detail['concept'],
                    'amount' => $detail['amount'],
                    'type' => 'deduction',
                    'description' => $detail['type']
                ]);
            }
        });
    }
}
