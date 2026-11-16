<?php

namespace Tests\Feature\Payroll;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AdminPanel\Models\Afp;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmploymentContract;
use Modules\Payroll\Models\EmployeeItem;
use Modules\Payroll\Models\Item;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Users\Models\User;
use Tests\Support\CreatesMonthlyIndicator;
use Tests\TestCase;

/**
 * Casos de referencia del motor único de cálculo, ejecutados por las rutas HTTP del cierre.
 * Indicadores de prueba: UF 40.000, UTM 70.000, IMM 500.000, tope AFP 90 UF, tope AFC 135 UF.
 */
class PayrollEngineTest extends TestCase
{
    use CreatesMonthlyIndicator;
    use RefreshDatabase;

    private User $actor;

    private Company $company;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createMonthlyIndicator(2026, 3);

        $this->company = Company::create([
            'name' => 'Empresa Motor',
            'razon_social' => 'Empresa Motor SpA',
            'rut' => '44444444-4',
            'gratification_system' => Company::GRATIFICATION_SYSTEM_NONE,
            'weekly_hours' => 42,
            'allows_overtime' => true,
        ]);

        app(TenantContext::class)->bypass();
        $this->actor = User::factory()->create(['company_id' => $this->company->id]);
        $this->period = PayrollPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2026,
            'month' => 3,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);
        app(TenantContext::class)->bypass(false);

        app(TenantContext::class)->initializeForUser($this->actor);
        $this->actingAs($this->actor);
    }

    public function test_indefinite_contract_with_afp_commission_and_income_tax(): void
    {
        $afp = Afp::create(['nombre' => 'HABITAT', 'rate' => 10.0, 'commission' => 1.27]);
        $employee = $this->employee(['salary' => 2000000, 'afp_id' => $afp->id, 'contract_type' => 'indefinido']);

        $line = $this->calculate($employee);

        $this->assertEquals(2000000, $line->gross_salary);
        $this->assertEquals(225400, $line->afp_amount);          // 11,27%
        $this->assertEquals(140000, $line->isapre_amount);       // 7%
        $this->assertEquals(12000, $line->cesantia_amount);      // 0,6%
        // Base tributable: 2.000.000 - 225.400 - 140.000 - 12.000 = 1.622.600 (23,18 UTM, tramo 4%)
        $this->assertEquals(1622600, $line->tributable_base);
        // 1.622.600 * 0,04 - 0,54 * 70.000 = 64.904 - 37.800
        $this->assertEquals(27104, $line->impuesto_unico_amount);
        $this->assertEquals(404504, $line->total_deductions);
        $this->assertEquals(1595496, $line->net_salary);

        // Costos del empleador
        $this->assertEquals(30000, $line->employer_sis_amount);  // 1,5%
        $this->assertEquals(48000, $line->employer_afc_amount);  // 2,4%
        $this->assertEquals(18600, $line->employer_mutual_amount); // 0,93%
        $this->assertEquals(2000, $line->employer_afp_amount);   // 0,1% a la cuenta individual (Ley 21.735)
        $this->assertEquals(32000, $line->employer_social_security_amount); // 0,9% + 0,7%
    }

    public function test_fixed_term_contract_pays_no_employee_unemployment_insurance(): void
    {
        $employee = $this->employee(['salary' => 800000, 'contract_type' => 'plazo_fijo']);

        $line = $this->calculate($employee);

        $this->assertEquals(0, $line->cesantia_amount);
        $this->assertEquals(24000, $line->employer_afc_amount);  // 3% de cargo del empleador
        $this->assertEquals(80000, $line->afp_amount);           // sin AFP asignada: 10% obligatorio
    }

    public function test_caps_apply_to_pension_health_and_unemployment_bases(): void
    {
        $employee = $this->employee(['salary' => 6000000, 'contract_type' => 'indefinido']);

        $line = $this->calculate($employee);

        $this->assertEquals(360000, $line->afp_amount);          // tope 3.600.000 * 10%
        $this->assertEquals(252000, $line->isapre_amount);       // tope 3.600.000 * 7%
        $this->assertEquals(32400, $line->cesantia_amount);      // tope 5.400.000 * 0,6%
    }

    public function test_isapre_plan_apv_and_family_allowance(): void
    {
        $employee = $this->employee([
            'salary' => 1000000,
            'contract_type' => 'indefinido',
            'health_system' => 'isapre',
            'health_contribution' => 120000,
            'health_contribution_unit' => 'CLP',
            'apv_amount' => 50000,
            'apv_regime' => 'B',
            'num_dependents' => 2,
        ]);

        $line = $this->calculate($employee);

        // El plan pactado supera el 7% legal (70.000) y se descuenta completo.
        $this->assertEquals(120000, $line->isapre_amount);
        $this->assertEquals(50000, $line->apv_amount);
        // Base tributable: 1.000.000 - 100.000 (AFP) - 120.000 - 6.000 (AFC) - 50.000 (APV)
        $this->assertEquals(724000, $line->tributable_base);
        $this->assertEquals(0, $line->impuesto_unico_amount);    // bajo 13,5 UTM
        // Tramo C (900.001 a 1.400.000): $4.000 por carga, no imponible.
        $this->assertEquals(8000, $line->asignacion_familiar_amount);
        $this->assertEquals(1008000, $line->total_earnings);
        $this->assertEquals(1008000 - 276000, $line->net_salary);
    }

    public function test_indefinite_contract_with_eleven_years_stops_employee_unemployment_insurance(): void
    {
        $employee = $this->employee(['salary' => 1000000, 'contract_type' => 'indefinido', 'hire_date' => '2015-03-01']);

        $line = $this->calculate($employee);

        $this->assertEquals(0, $line->cesantia_amount);
        $this->assertEquals(8000, $line->employer_afc_amount);   // 0,8% de cargo del empleador
    }

    public function test_ccaf_share_is_part_of_the_fonasa_contribution_not_an_extra_deduction(): void
    {
        app(TenantContext::class)->bypass();
        $ccaf = \Modules\AdminPanel\Models\Ccaf::create(['nombre' => 'Caja de prueba']);
        $this->company->update(['ccaf_id' => $ccaf->id]);
        app(TenantContext::class)->bypass(false);
        app(TenantContext::class)->initializeForUser($this->actor);

        $fonasa = $this->employee(['salary' => 1000000, 'health_system' => 'fonasa']);
        $isapre = $this->employee(['salary' => 1000000, 'health_system' => 'isapre', 'health_contribution' => 90000, 'health_contribution_unit' => 'CLP']);

        $this->calculate($fonasa);

        $this->assertEquals(70000, $this->line($fonasa)->isapre_amount);  // el trabajador sigue pagando 7%
        $this->assertEquals(6000, $this->line($fonasa)->ccaf_amount);     // 0,6% va a la caja
        $this->assertEquals(0, $this->line($isapre)->ccaf_amount);
    }

    public function test_voluntary_deductions_over_fifteen_percent_are_flagged(): void
    {
        $employee = $this->employee(['salary' => 1000000]);
        $this->assign($employee, Item::TYPE_DESCUENTO_VARIOS, 'Convenio', 200000);

        $line = $this->calculate($employee);

        // Límite: 15% de 1.000.000 = 150.000. El descuento se aplica completo y se informa el exceso.
        $this->assertEquals(200000, $line->descuentos_items_amount);
        $this->assertEquals(50000, $line->descuentos_exceso_amount);
    }

    public function test_isapre_plan_in_uf_and_in_percentage(): void
    {
        $inUf = $this->employee(['salary' => 1000000, 'health_system' => 'isapre', 'health_contribution' => 3.25]);
        $inPercentage = $this->employee([
            'salary' => 1000000, 'health_system' => 'isapre',
            'health_contribution' => 8.5, 'health_contribution_unit' => 'PERCENTAGE',
        ]);
        $belowLegal = $this->employee(['salary' => 1000000, 'health_system' => 'isapre', 'health_contribution' => 1]);

        $this->calculate($inUf);

        $this->assertEquals(130000, $this->line($inUf)->isapre_amount);          // 3,25 UF * 40.000 (unidad por defecto)
        $this->assertEquals(85000, $this->line($inPercentage)->isapre_amount);   // 8,5% del imponible
        $this->assertEquals(70000, $this->line($belowLegal)->isapre_amount);     // 1 UF < 7% legal
    }

    public function test_apv_regime_a_is_deducted_but_does_not_lower_the_tax_base(): void
    {
        $regimeA = $this->employee(['salary' => 2000000, 'apv_amount' => 100000]);
        $regimeB = $this->employee(['salary' => 2000000, 'apv_amount' => 100000, 'apv_regime' => 'B']);

        $this->calculate($regimeA);

        $this->assertEquals(100000, $this->line($regimeA)->apv_amount);
        $this->assertEquals(1648000, $this->line($regimeA)->tributable_base);    // 2.000.000 - 200.000 - 140.000 - 12.000
        $this->assertEquals(1548000, $this->line($regimeB)->tributable_base);
        $this->assertGreaterThan($this->line($regimeB)->impuesto_unico_amount, $this->line($regimeA)->impuesto_unico_amount);
        $this->assertEquals($this->line($regimeA)->total_deductions - $this->line($regimeA)->impuesto_unico_amount,
            $this->line($regimeB)->total_deductions - $this->line($regimeB)->impuesto_unico_amount);
    }

    public function test_family_allowance_uses_the_recognized_bracket_when_informed(): void
    {
        $recognized = $this->employee(['salary' => 1000000, 'num_dependents' => 1, 'family_allowance_bracket' => 'A']);
        $estimated = $this->employee(['salary' => 1000000, 'num_dependents' => 1]);

        $this->calculate($recognized);

        $this->assertEquals(20000, $this->line($recognized)->asignacion_familiar_amount); // tramo A informado
        $this->assertEquals(4000, $this->line($estimated)->asignacion_familiar_amount);   // tramo C por renta del mes
    }

    public function test_overtime_uses_the_weekly_hours_of_the_company(): void
    {
        $employee = $this->employee(['salary' => 840000, 'contract_type' => 'indefinido']);
        $line = $this->calculate($employee);

        $this->putJson(route('companies.payroll-periods.update-line', [$this->company, $this->period, $line]), [
            'overtime_hours' => 10,
        ])->assertOk();

        // 840.000 * (7 / 30 / 42) * 1,5 = 7.000 por hora
        $this->assertEquals(70000, $line->fresh()->overtime_amount);
    }

    public function test_mid_month_hire_and_termination_are_prorated_and_stay_in_headcount(): void
    {
        $hired = $this->employee(['salary' => 900000, 'hire_date' => '2026-03-16']);
        $terminated = $this->employee(['salary' => 900000, 'status' => 'inactive']);
        $this->contract($terminated, ['status' => EmploymentContract::STATUS_TERMINATED, 'termination_date' => '2026-03-10']);
        $gone = $this->employee(['salary' => 900000, 'status' => 'inactive']);
        $this->contract($gone, ['status' => EmploymentContract::STATUS_TERMINATED, 'termination_date' => '2026-02-20']);
        $future = $this->employee(['salary' => 900000, 'hire_date' => '2026-04-01']);

        $this->calculate($hired);

        $this->assertEquals(15, $this->line($hired)->worked_days);       // 30 - 15 días previos al ingreso
        $this->assertEquals(450000, $this->line($hired)->gross_salary);
        $this->assertEquals(10, $this->line($terminated)->worked_days);  // del 1 al 10
        $this->assertEquals(300000, $this->line($terminated)->gross_salary);
        $this->assertNull($this->line($gone));
        $this->assertNull($this->line($future));
    }

    public function test_recalculating_keeps_amounts_and_charges_one_installment_per_period(): void
    {
        $employee = $this->employee(['salary' => 500000, 'contract_type' => 'indefinido']);
        $credit = $this->assign($employee, Item::TYPE_CREDITO, 'Préstamo', 1000, ['total_installments' => 4, 'current_installment' => 0]);
        $this->assign($employee, Item::TYPE_DESCUENTO_VARIOS, 'Cuota sindical', 5000);

        $line = $this->calculate($employee);
        $this->putJson(route('companies.payroll-periods.update-line', [$this->company, $this->period, $line]), [
            'otros_descuentos' => 3000,
        ])->assertOk();

        $first = $line->fresh();
        // 500.000 - AFP 50.000 - salud 35.000 - AFC 3.000 - crédito 1.000 - sindicato 5.000 - manual 3.000
        $this->assertEquals(403000, $first->net_salary);
        $this->assertEquals(3000, $first->otros_descuentos);
        $this->assertEquals(1000, $first->creditos_amount);
        $this->assertEquals(5000, $first->descuentos_items_amount);

        $this->calculate($employee);
        $this->calculate($employee);
        $again = $line->fresh();

        $this->assertEquals(403000, $again->net_salary);
        $this->assertEquals(3000, $again->otros_descuentos);
        $this->assertEquals(97000, $again->total_deductions);
        $this->assertEquals(1, $credit->fresh()->current_installment);
        $this->assertEquals(1, $again->details()->where('concept', 'CREDITO')->count());
        $this->assertEquals(1, $again->details()->where('concept', 'CREDITO')->value('installment_number'));
    }

    public function test_last_installment_survives_recalculation_after_the_credit_is_completed(): void
    {
        $employee = $this->employee(['salary' => 500000]);
        $credit = $this->assign($employee, Item::TYPE_CREDITO, 'Préstamo', 1000, ['total_installments' => 2, 'current_installment' => 1]);

        $this->calculate($employee);
        $this->assertFalse($credit->fresh()->is_active);

        $line = $this->calculate($employee);

        $this->assertEquals(1000, $line->creditos_amount);
        $this->assertEquals(2, $credit->fresh()->current_installment);
    }

    public function test_closed_period_rejects_calculation_and_line_edits(): void
    {
        $employee = $this->employee(['salary' => 500000]);
        $credit = $this->assign($employee, Item::TYPE_CREDITO, 'Préstamo', 1000, ['total_installments' => 4, 'current_installment' => 0]);
        $line = $this->calculate($employee);
        $netBefore = $line->net_salary;

        $this->postJson(route('companies.payroll-periods.update-status', [$this->company, $this->period]), ['status' => 'closed'])
            ->assertOk();

        app(TenantContext::class)->initializeForUser($this->actor);
        $employee->update(['salary' => 900000]);

        $this->post(route('companies.payroll-periods.calculate', [$this->company, $this->period]))
            ->assertSessionHasErrors('error');
        $this->putJson(route('companies.payroll-periods.update-line', [$this->company, $this->period, $line]), [
            'otros_descuentos' => 9999,
            'details' => [['type' => 'CO01', 'description' => 'Comisión', 'amount' => 100000]],
        ])->assertStatus(422)->assertJson(['success' => false]);

        app(TenantContext::class)->initializeForUser($this->actor);
        $after = $line->fresh();
        $this->assertSame(PayrollPeriod::STATUS_CLOSED, $this->period->fresh()->status);
        $this->assertEquals($netBefore, $after->net_salary);
        $this->assertEquals(0, $after->otros_descuentos);
        $this->assertEquals(0, $after->details()->where('concept', 'CO01')->count());
        $this->assertEquals(1, $credit->fresh()->current_installment);
    }

    public function test_calculation_is_refused_without_indicators_for_the_period(): void
    {
        $employee = $this->employee(['salary' => 500000]);
        app(TenantContext::class)->bypass();
        $april = PayrollPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2026,
            'month' => 4,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);
        app(TenantContext::class)->bypass(false);
        app(TenantContext::class)->initializeForUser($this->actor);

        $this->post(route('companies.payroll-periods.calculate', [$this->company, $april]))
            ->assertSessionHasErrors('error');

        app(TenantContext::class)->initializeForUser($this->actor);
        $this->assertSame(PayrollPeriod::STATUS_DRAFT, $april->fresh()->status);
        $this->assertSame(0, Payroll::where('payroll_period_id', $april->id)->count());
    }

    private function employee(array $attributes): Employee
    {
        app(TenantContext::class)->bypass();
        $user = User::factory()->create();
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'status' => 'active',
        ], $attributes));
        app(TenantContext::class)->bypass(false);
        app(TenantContext::class)->initializeForUser($this->actor);

        return $employee;
    }

    private function contract(Employee $employee, array $attributes): void
    {
        app(TenantContext::class)->bypass();
        $contract = $employee->syncActiveContract();
        $contract->update($attributes);
        app(TenantContext::class)->bypass(false);
        app(TenantContext::class)->initializeForUser($this->actor);
    }

    private function assign(Employee $employee, string $type, string $name, int $amount, array $extra = []): EmployeeItem
    {
        $item = Item::create([
            'company_id' => $this->company->id,
            'name' => $name,
            'code' => strtoupper(substr(md5($name), 0, 8)),
            'type' => $type,
        ]);

        return EmployeeItem::create(array_merge([
            'employee_id' => $employee->id,
            'item_id' => $item->id,
            'amount' => $amount,
            'unit' => 'CLP',
            'periodicity' => 'fixed',
            'is_active' => true,
        ], $extra));
    }

    private function calculate(Employee $employee): Payroll
    {
        $this->post(route('companies.payroll-periods.calculate', [$this->company, $this->period]))
            ->assertRedirect()->assertSessionHasNoErrors();
        app(TenantContext::class)->initializeForUser($this->actor);

        return $this->line($employee);
    }

    private function line(Employee $employee): ?Payroll
    {
        return $this->period->payrolls()->where('employee_id', $employee->id)->first();
    }
}
