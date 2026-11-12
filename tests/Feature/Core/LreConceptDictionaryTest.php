<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Database\Seeders\LreConceptsSeeder;
use Modules\Core\Models\LreConcept;
use Tests\TestCase;

class LreConceptDictionaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_loads_official_dt_lre_concepts_with_labor_code_flags(): void
    {
        $this->seed(LreConceptsSeeder::class);

        $this->assertGreaterThanOrEqual(20, LreConcept::query()->count());

        // 1101: Sueldo Base (Art. 42 letra a CT)
        $baseSalary = LreConcept::query()->where('code', '1101')->firstOrFail();
        $this->assertTrue($baseSalary->is_taxable);
        $this->assertTrue($baseSalary->is_taxable_afc);
        $this->assertTrue($baseSalary->is_tributable);
        $this->assertTrue($baseSalary->is_gratification_base);
        $this->assertTrue($baseSalary->is_overtime_base);
        $this->assertTrue($baseSalary->is_severance_base);

        // 1102: Sobresueldo / Horas Extras (imponible y base gratificación, pero no base horas extras ni indemnización)
        $overtime = LreConcept::query()->where('code', '1102')->firstOrFail();
        $this->assertTrue($overtime->is_taxable);
        $this->assertTrue($overtime->is_gratification_base);
        $this->assertFalse($overtime->is_overtime_base);
        $this->assertFalse($overtime->is_severance_base);

        // 2101: Colación (Art. 41 inc. 2 CT - No imponible ni tributable)
        $mealAllowance = LreConcept::query()->where('code', '2101')->firstOrFail();
        $this->assertFalse($mealAllowance->is_taxable);
        $this->assertFalse($mealAllowance->is_tributable);
        $this->assertSame(LreConcept::CLASSIFICATION_NON_TAXABLE_EARNING, $mealAllowance->classification);

        // 3101: Cotización Obligatoria AFP (Art. 58 CT)
        $afpDeduction = LreConcept::query()->where('code', '3101')->firstOrFail();
        $this->assertSame(LreConcept::CLASSIFICATION_LEGAL_DEDUCTION, $afpDeduction->classification);

        $this->assertGreaterThanOrEqual(6, LreConcept::query()->taxable()->count());
        $this->assertGreaterThanOrEqual(5, LreConcept::query()->nonTaxable()->count());
        $this->assertGreaterThanOrEqual(5, LreConcept::query()->legalDeductions()->count());
    }
}

