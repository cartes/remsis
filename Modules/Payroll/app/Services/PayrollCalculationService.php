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
        /** @var \Illuminate\Database\Eloquent\Collection<int, Employee> $employees */
        $employees = Employee::where('company_id', $period->company_id)
            ->where('status', 'active')
            ->where('is_in_payroll', true)
            ->with(['employeeItems.item']) // evitar N+1
            ->get();

        $count = 0;

        DB::transaction(function () use ($employees, $period, &$count) {
            foreach ($employees as $employee) {
                $this->calculateEmployee($employee, $period);
                $count++;
            }

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

        // Parámetros legales para resolver UF/UTM
        $uf  = LegalParameter::where('key', 'uf_value')->first() ?? (object)['value' => 37000];
        $utm = LegalParameter::where('key', 'utm_value')->first() ?? (object)['value' => 65182];

        // 1. Sueldo Base
        $haberBase = (int) ($employee->salary ?? 0);

        // 2. Horas Extras
        $overtimeAmount = 0;
        $overtimeHours  = 0;

        $existingLine = Payroll::where('employee_id', $employee->id)
            ->where('payroll_period_id', $period->id)
            ->first();

        if ($company->allows_overtime && $existingLine) {
            $overtimeHours = $existingLine->overtime_hours;
            $factor        = $company->weekly_hours == 44 ? 0.0079545 : 0.0077777;
            $overtimeAmount = (int) round($haberBase * $factor * $overtimeHours);
        }

        // 3. Montos manuales existentes
        $comisionesAmount    = $existingLine ? (int) $existingLine->comisiones_amount    : 0;
        $semanaCorridaAmount = $existingLine ? (int) $existingLine->semana_corrida_amount : 0;
        $bonosAmount         = $existingLine ? (int) $existingLine->bonos_imponibles_amount : 0;
        $aguinaldosAmount    = $existingLine ? (int) $existingLine->aguinaldos_amount    : 0;
        $gratificationAmount = $existingLine ? (int) $existingLine->gratification_amount : 0;
        $anticiposAmount     = $existingLine ? (int) $existingLine->anticipos_amount     : 0;
        $otrosDescuentos     = $existingLine ? (int) $existingLine->otros_descuentos     : 0;

        // 4. Ítems relacionales activos del colaborador
        $activeItems = $employee->relationLoaded('employeeItems')
            ? $employee->employeeItems->where('is_active', true)->filter(fn($ei) => $ei->item !== null)
            : $employee->employeeItems()->with('item')->where('is_active', true)->get();

        $haberesImponibles   = $activeItems->filter(fn($ei) => $ei->item->type === 'haber_imponible');
        $haberesNoImponibles = $activeItems->filter(fn($ei) => $ei->item->type === 'haber_no_imponible');
        $descuentosVarios    = $activeItems->filter(fn($ei) => $ei->item->type === 'descuento_varios');
        $creditos            = $activeItems->filter(fn($ei) => $ei->item->type === 'credito');

        // Base para PERCENTAGE usa sueldo base antes de ítems
        $baseParaPercentage = $haberBase;

        $totalHaberesImponibles   = $haberesImponibles->sum(fn($ei) => $ei->resolvedAmountCLP($uf, $utm, $baseParaPercentage));
        $totalHaberesNoImponibles = $haberesNoImponibles->sum(fn($ei) => $ei->resolvedAmountCLP($uf, $utm, $baseParaPercentage));
        $totalDescuentosVarios    = $descuentosVarios->sum(fn($ei) => $ei->resolvedAmountCLP($uf, $utm, $baseParaPercentage));
        $totalCreditos            = $creditos->sum(fn($ei) => $ei->resolvedAmountCLP($uf, $utm, $baseParaPercentage));

        // Colación y movilización histórica para la tabla payrolls (compatibilidad)
        $mealAllowance     = (int) $haberesNoImponibles->filter(fn($ei) => $ei->item->code === 'COLACION')->sum(fn($ei) => $ei->resolvedAmountCLP($uf, $utm, $baseParaPercentage));
        $mobilityAllowance = (int) $haberesNoImponibles->filter(fn($ei) => $ei->item->code === 'MOVILIZACION')->sum(fn($ei) => $ei->resolvedAmountCLP($uf, $utm, $baseParaPercentage));

        // 5. Gratificación
        $baseGratificacion = $haberBase + $overtimeAmount + $comisionesAmount + $semanaCorridaAmount
            + $bonosAmount + $aguinaldosAmount
            + $haberesImponibles->filter(fn($ei) => $ei->item->is_gratification_base)->sum(fn($ei) => $ei->resolvedAmountCLP($uf, $utm, $baseParaPercentage));

        if ($company->gratification_system === 'art_50') {
            $immParam        = LegalParameter::where('key', 'monthly_minimum_wage')->value('value');
            $imm             = (is_numeric($immParam) && $immParam > 0) ? $immParam : 500000;
            $topeMensual     = (4.75 * $imm) / 12;
            $gratificationAmount = (int) round(min($baseGratificacion * 0.25, $topeMensual));
        } elseif ($company->gratification_system === 'sin_gratificacion') {
            $gratificationAmount = 0;
        }

        // 6. Imponible total
        $imponible = (int) ($haberBase + $overtimeAmount + $comisionesAmount + $semanaCorridaAmount
            + $bonosAmount + $aguinaldosAmount + $gratificationAmount + $totalHaberesImponibles);

        // 7. Total haberes (imponible + no imponible)
        $nonTaxableEarnings = $totalHaberesNoImponibles;
        $totalEarnings      = $imponible + $nonTaxableEarnings;

        // 8. Deducciones legales
        $afpAmount     = (int) round($imponible * 0.10);
        $saludAmount   = (int) round($imponible * 0.07);
        $cesantiaAmount = (int) round($imponible * 0.006);

        $totalDeductions = $afpAmount + $saludAmount + $cesantiaAmount
            + $anticiposAmount + $otrosDescuentos
            + $totalDescuentosVarios + $totalCreditos;

        $totalNeto = $totalEarnings - $totalDeductions;

        // 9. Crear/actualizar registro de nómina
        $payroll = Payroll::updateOrCreate(
            [
                'employee_id'      => $employee->id,
                'payroll_period_id' => $period->id,
            ],
            [
                'user_id'    => $employee->user_id,
                'company_id' => $period->company_id,
                'period_year'  => $period->year,
                'period_month' => $period->month,
                'worked_days'  => 30,

                'overtime_hours'          => $overtimeHours,
                'overtime_amount'         => $overtimeAmount,
                'comisiones_amount'       => $comisionesAmount,
                'semana_corrida_amount'   => $semanaCorridaAmount,
                'bonos_imponibles_amount' => $bonosAmount,
                'aguinaldos_amount'       => $aguinaldosAmount,

                'base_salary'          => $haberBase,
                'gratification_amount' => $gratificationAmount,
                'gross_salary'         => $imponible,
                'meal_allowance'       => $mealAllowance,
                'mobility_allowance'   => $mobilityAllowance,
                'non_taxable_earnings' => $nonTaxableEarnings,
                'total_earnings'       => $totalEarnings,

                'afp_id'      => $employee->afp_id,
                'afp_amount'  => $afpAmount,
                'isapre_id'   => $employee->isapre_id,
                'isapre_amount' => $saludAmount,
                'ccaf_id'     => $employee->ccaf_id,
                'ccaf_amount' => 0,

                'cesantia_amount'       => $cesantiaAmount,
                'impuesto_unico_amount' => 0,
                'anticipos_amount'      => $anticiposAmount,
                'otros_descuentos'      => $otrosDescuentos + $totalDescuentosVarios + $totalCreditos,

                'total_deductions' => $totalDeductions,
                'net_salary'       => $totalNeto,

                'status' => 'pending',
            ]
        );

        // 10. Incrementar cuotas de créditos activos
        foreach ($creditos as $ei) {
            $newInstallment = ($ei->current_installment ?? 0) + 1;
            $updates = ['current_installment' => $newInstallment];
            if ($ei->total_installments !== null && $newInstallment >= $ei->total_installments) {
                $updates['is_active'] = false;
            }
            $ei->update($updates);
        }

        return $payroll;
    }
}
