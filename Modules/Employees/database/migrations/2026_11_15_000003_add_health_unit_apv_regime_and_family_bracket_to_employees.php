<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - health_contribution_unit: unidad del plan de Isapre (UF, CLP o PERCENTAGE de la renta imponible).
 * - apv_regime: régimen tributario del APV. A no rebaja la base del impuesto único; B sí.
 * - family_allowance_bracket: tramo de asignación familiar reconocido al trabajador (A-D).
 *   Si queda vacío, el cálculo lo estima con la renta imponible del mes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'health_contribution_unit')) {
                $table->string('health_contribution_unit', 12)->default('UF');
            }
            if (! Schema::hasColumn('employees', 'apv_regime')) {
                $table->string('apv_regime', 1)->default('A');
            }
            if (! Schema::hasColumn('employees', 'family_allowance_bracket')) {
                $table->string('family_allowance_bracket', 1)->nullable();
            }
        });

        // Los planes ya ingresados no tenían unidad: un valor de 100 o más sólo puede ser un monto en pesos.
        DB::table('employees')->where('health_contribution', '>=', 100)->update(['health_contribution_unit' => 'CLP']);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach (['health_contribution_unit', 'apv_regime', 'family_allowance_bracket'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
