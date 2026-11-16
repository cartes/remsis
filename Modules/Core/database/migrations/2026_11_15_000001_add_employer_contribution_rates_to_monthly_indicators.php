<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tasas de cargo del empleador de la reforma previsional (Ley 21.735) y del seguro
 * de cesantía para contratos indefinidos con 11 años o más.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'employer_afp_rate',
        'employer_protected_return_rate',
        'employer_life_expectancy_rate',
        'afc_indefinite_11y_employer_rate',
    ];

    public function up(): void
    {
        Schema::table('monthly_indicators', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (! Schema::hasColumn('monthly_indicators', $column)) {
                    $table->decimal($column, 5, 2)->default(0);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_indicators', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('monthly_indicators', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
