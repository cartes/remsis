<?php

namespace Modules\Payroll\Tests\Unit\Services;

use Tests\TestCase;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\AdminPanel\Models\LegalParameter;
use Modules\AdminPanel\Models\Afp;
use Modules\Companies\Models\Company;
use Modules\Users\Models\User;
use Modules\Payroll\Services\ProcessEmployeePayroll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ProcessEmployeePayrollTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuración de parámetros legales compartidos para las pruebas
        LegalParameter::updateOrCreate(['key' => 'monthly_minimum_wage'], ['value' => 500000]);
        LegalParameter::updateOrCreate(['key' => 'uf_value'], ['value' => 37000]);
        LegalParameter::updateOrCreate(['key' => 'utm_value'], ['value' => 65000]);

        // Creación de datos base para restricciones de llaves foráneas
        $this->company = Company::create([
            'name' => 'Test Company',
            'rut' => '12.345.678-9',
            'email' => 'test@company.com'
        ]);

        $this->user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password')
        ]);
    }

    /** @test */
    public function it_calculates_standard_payroll_correctly()
    {
        // 1. Arrange (Preparar)
        $afp = Afp::create(['nombre' => 'MODELO', 'rate' => 10.0, 'commission' => 0.58]);
        
        $employee = Employee::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'salary' => 1000000,
            'afp_id' => $afp->id,
            'contract_type' => 'indefinido',
            'meal_allowance' => 50000,
            'mobility_allowance' => 50000,
            'status' => 'active',
            'is_in_payroll' => true
        ]);

        $period = PayrollPeriod::create([
            'company_id' => $this->company->id,
            'year' => 2026,
            'month' => 3,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => 'open'
        ]);

        $service = new ProcessEmployeePayroll();

        // 2. Act (Ejecutar)
        $resultDTO = $service->execute($employee->id, $period->id);
        $result = $resultDTO->toArray();

        // 3. Assert (Verificar)
        
        // Haberes: Base (1,000,000) + Gratificación (Min(250,000, 197,917)) = 1,197,917
        $this->assertEquals('$1.000.000', $result['summary']['base_salary']);
        $this->assertEquals('$197.917', $result['summary']['gratification']); // Verificación del tope
        $this->assertEquals('$1.197.917', $result['summary']['taxable_earnings']);
        $this->assertEquals('$100.000', $result['summary']['non_taxable_earnings']);
        $this->assertEquals('$1.297.917', $result['summary']['total_earnings']);

        // Descuentos:
        // AFP: 1,197,917 * 0.1058 = 126,740
        $this->assertEquals('$126.740', $result['legal_deductions']['afp']);
        
        // Salud: 1,197,917 * 0.07 = 83,854
        $this->assertEquals('$83.854', $result['legal_deductions']['health']);
        
        // AFC: 1,197,917 * 0.006 = 7,188
        $this->assertEquals('$7.188', $result['legal_deductions']['afc']);

        // IUSC (Impuesto Único):
        // Base: 1,197,917 - 126,740 - 83,854 - 7,188 = 980,135
        // UTM: Marzo 2026 (65,000 presumido). 980,135 / 65,000 = 15.07 UTM
        // Tramo 2 (13.5 - 30): Factor 0.04, Descuento 0.54 UTM
        // (980,135 * 0.04) - (0.54 * 65,000) = 39,205 - 35,100 = 4,105
        $this->assertEquals('$4.105', $result['legal_deductions']['iusc']);
    }

    /** @test */
    public function it_respects_afp_and_afc_caps()
    {
        // UF = 37,000. Tope AFP = 80.2 UF = 2,967,400. Tope AFC = 120.3 UF = 4,451,100.
        // Imponible = 4,000,000.
        
        $this->company->update(['allows_overtime' => true]); // Corrección contextual si es necesaria
        
        $afp = Afp::create(['nombre' => 'HABITAT', 'rate' => 10.0, 'commission' => 1.27]);
        
        $employee = Employee::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'salary' => 4000000,
            'afp_id' => $afp->id,
            'contract_type' => 'indefinido'
        ]);

        $period = PayrollPeriod::create([
            'company_id' => $this->company->id, 
            'year' => 2026, 
            'month' => 3,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31'
        ]);
        
        $service = new ProcessEmployeePayroll();
        $result = $service->execute($employee->id, $period->id)->toArray();

        // La AFP debería topar en 2,967,400 * 0.1127 = 334,426
        $this->assertEquals('$334.426', $result['legal_deductions']['afp']);
        
        // La AFC no debería topar (4.19M < 4.45M). 
        // Imponible = 4,000,000 + 197,917 = 4,197,917.
        // 4,197,917 * 0.006 = 25,187.502 -> 25,188
        $this->assertEquals('$25.188', $result['legal_deductions']['afc']);
    }
}
