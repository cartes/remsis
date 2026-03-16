<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('meal_allowance', 10, 2)->default(0.00)->after('salary')->comment('Asignación de Colación (No Imponible)');
            $table->decimal('mobility_allowance', 10, 2)->default(0.00)->after('meal_allowance')->comment('Asignación de Movilización (No Imponible)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['meal_allowance', 'mobility_allowance']);
        });
    }
};
