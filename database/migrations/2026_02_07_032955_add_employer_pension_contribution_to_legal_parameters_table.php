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
        DB::table('legal_parameters')->insert([
            'key' => 'employer_pension_contribution_rate',
            'value' => '0.0', // Initially 0, increases gradually
            'label' => 'Cotización Adicional Empleador (%)',
            'description' => 'Aporte adicional de cargo del empleador para mejorar pensiones (Reforma Previsional).',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('legal_parameters')->where('key', 'employer_pension_contribution_rate')->delete();
    }
};
