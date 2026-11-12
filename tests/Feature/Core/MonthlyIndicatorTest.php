<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Database\Seeders\MonthlyIndicatorSeeder;
use Modules\Core\Models\MonthlyIndicator;
use Tests\TestCase;

class MonthlyIndicatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_populates_monthly_indicator_with_afp_iut_and_family_brackets(): void
    {
        $this->seed(MonthlyIndicatorSeeder::class);

        $indicator = MonthlyIndicator::query()->first();

        $this->assertNotNull($indicator);
        $this->assertCount(7, $indicator->afpRateRecords);
        $this->assertCount(8, $indicator->iutBracketRecords);
        $this->assertCount(4, $indicator->familyAllowanceBracketRecords);

        // AFP Habitat: 10% obligatorio + 1.27% comisión = 11.27%
        $this->assertSame(11.27, $indicator->getAfpRate('habitat'));
        // AFP Uno: 10% obligatorio + 0.49% comisión = 10.49%
        $this->assertSame(10.49, $indicator->getAfpRate('uno'));
    }

    public function test_monthly_indicator_calculates_caps_and_taxes_rounded_to_zero_decimals(): void
    {
        $this->seed(MonthlyIndicatorSeeder::class);

        $indicator = MonthlyIndicator::query()->firstOrFail();
        $indicator->update([
            'uf_value' => 38500.00,
            'uf_previous_month' => 38400.00,
            'utm_value' => 68000.00,
            'imm_value' => 510636,
        ]);

        // Tope AFP: 84.3 UF * 38.400 = 3.237.120 CLP
        $this->assertSame(3237120.0, $indicator->getAfpCapClp());

        // Tope AFC: 126.6 UF * 38.400 = 4.861.440 CLP
        $this->assertSame(4861440.0, $indicator->getAfcCapClp());

        // Tope Gratificación Art. 50: (510.636 * 4.75) / 12 = 202.127 CLP
        $this->assertSame(202127.0, $indicator->getGratificationCapClp());

        // Renta exenta (<= 13.5 UTM = 918.000): IUT = 0
        $this->assertSame(0.0, $indicator->calculateIut(850000));

        // Renta en Tramo 2 (20 UTM = 1.360.000): (20 * 0.04 - 0.54) * 68.000 = 0.26 * 68.000 = 17.680 CLP
        $this->assertSame(17680.0, $indicator->calculateIut(1360000));

        // Asignación familiar Tramo A, B, C, D
        $this->assertSame(21243.0, $indicator->getFamilyAllowancePerDependent(510636));
        $this->assertSame(13036.0, $indicator->getFamilyAllowancePerDependent(750000));
        $this->assertSame(4119.0, $indicator->getFamilyAllowancePerDependent(1100000));
        $this->assertSame(0.0, $indicator->getFamilyAllowancePerDependent(2000000));
    }
}

