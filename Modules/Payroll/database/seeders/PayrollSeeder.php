<?php

namespace Modules\Payroll\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Employees\Models\Employee;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\Isapre;
use Modules\AdminPanel\Models\Ccaf;
use Carbon\Carbon;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los empleados
        $employees = Employee::with(['user', 'company'])->get();
        
        if ($employees->isEmpty()) {
            $this->command->warn('No hay empleados en la base de datos. Ejecuta primero CompanyUserSeeder.');
            return;
        }

        // Obtener períodos
        $periods = PayrollPeriod::where('status', 'closed')->get();
        
        if ($periods->isEmpty()) {
            $this->command->warn('No hay períodos de nómina. Ejecuta primero PayrollPeriodSeeder.');
            return;
        }

        // Obtener entidades para previsión
        $afps = Afp::all();
        $isapres = Isapre::all();
        $ccafs = Ccaf::all();

        $statuses = ['pending', 'processed', 'paid'];

        foreach ($employees as $employee) {
            // Crear nóminas para los últimos 6 períodos
            $recentPeriods = $periods->take(6);
            
            foreach ($recentPeriods as $period) {
                $baseSalary = rand(500000, 2500000);
                $grossSalary = $baseSalary + rand(0, 200000); // Bonos, horas extra, etc.
                
                // Calcular descuentos (aproximados)
                $afpAmount = $grossSalary * 0.1273; // 12.73% AFP
                $isapreAmount = $grossSalary * 0.07; // 7% Isapre
                $ccafAmount = 0; // CCAF no tiene descuento directo al trabajador
                $cesantiaAmount = $grossSalary * 0.006; // 0.6% Seguro de Cesantía
                $impuestoUnico = $this->calculateImpuestoUnico($grossSalary);
                
                $totalDeductions = $afpAmount + $isapreAmount + $cesantiaAmount + $impuestoUnico;
                $netSalary = $grossSalary - $totalDeductions;

                Payroll::create([
                    'user_id' => $employee->user_id,
                    'employee_id' => $employee->id,
                    'company_id' => $employee->company_id,
                    'payroll_period_id' => $period->id,
                    'period_year' => Carbon::parse($period->start_date)->year,
                    'period_month' => Carbon::parse($period->start_date)->month,
                    'worked_days' => rand(28, 30),
                    'overtime_hours' => rand(0, 20),
                    'base_salary' => $baseSalary,
                    'gross_salary' => $grossSalary,
                    'afp_id' => $afps->random()->id ?? null,
                    'afp_amount' => round($afpAmount, 2),
                    'isapre_id' => $isapres->random()->id ?? null,
                    'isapre_amount' => round($isapreAmount, 2),
                    'ccaf_id' => $ccafs->random()->id ?? null,
                    'ccaf_amount' => round($ccafAmount, 2),
                    'cesantia_amount' => round($cesantiaAmount, 2),
                    'impuesto_unico_amount' => round($impuestoUnico, 2),
                    'anticipos_amount' => 0,
                    'otros_descuentos' => 0,
                    'total_deductions' => round($totalDeductions, 2),
                    'net_salary' => round($netSalary, 2),
                    'payment_date' => $period->payment_date,
                    'status' => $statuses[array_rand($statuses)],
                    'bank_name' => $employee->bank->name ?? 'Banco Estado',
                    'bank_account_number' => $employee->bank_account_number ?? rand(10000000, 99999999),
                    'bank_account_type' => $employee->bank_account_type ?? 'vista',
                    'processed_at' => now(),
                    'processed_by' => 1, // Super Admin
                    'notes' => null,
                ]);
            }
        }

        $this->command->info('Nóminas creadas exitosamente para ' . $employees->count() . ' empleados.');
    }

    /**
     * Cálculo simplificado del impuesto único
     */
    private function calculateImpuestoUnico($grossSalary)
    {
        // Tabla simplificada de impuesto único 2024
        if ($grossSalary <= 866196) {
            return 0;
        } elseif ($grossSalary <= 1924880) {
            return ($grossSalary - 866196) * 0.04;
        } elseif ($grossSalary <= 3208134) {
            return 42347 + ($grossSalary - 1924880) * 0.08;
        } elseif ($grossSalary <= 4491388) {
            return 145007 + ($grossSalary - 3208134) * 0.135;
        } elseif ($grossSalary <= 5774642) {
            return 318246 + ($grossSalary - 4491388) * 0.23;
        } elseif ($grossSalary <= 7699523) {
            return 613394 + ($grossSalary - 5774642) * 0.304;
        } else {
            return 1198478 + ($grossSalary - 7699523) * 0.35;
        }
    }
}
