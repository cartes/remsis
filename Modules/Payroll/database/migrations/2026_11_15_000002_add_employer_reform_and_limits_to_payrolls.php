<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - employer_afp_amount: 0,1% del empleador a la cuenta individual del trabajador (Ley 21.735).
 * - employer_social_security_amount: rentabilidad protegida + expectativa de vida (seguro social).
 * - descuentos_exceso_amount: monto en que los descuentos voluntarios superan el 15% del Art. 58 CT.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'employer_afp_amount',
        'employer_social_security_amount',
        'descuentos_exceso_amount',
    ];

    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (! Schema::hasColumn('payrolls', $column)) {
                    $table->decimal($column, 12, 2)->default(0);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
