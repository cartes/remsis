<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('legal_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default values
        $defaults = [
            
            // Financial Indicators
            [
                'key' => 'monthly_minimum_wage',
                'value' => '500000',
                'label' => 'Salario Mínimo Mensual',
                'description' => 'Valor actual del salario mínimo mensual.'
            ],
            [
                'key' => 'utm_value',
                'value' => '64793', 
                'label' => 'Valor UTM',
                'description' => 'Unidad Tributaria Mensual (UTM) vigente.'
            ],
            [
                'key' => 'uf_value',
                'value' => '36863.36',
                'label' => 'Valor UF',
                'description' => 'Unidad de Fomento (UF) vigente.'
            ],
            [
                'key' => 'ipc_value',
                'value' => '0.0', 
                'label' => 'IPC (%)',
                'description' => 'Variación porcentual del Índice de Precios al Consumidor.'
            ],

            // Labor Rules
            [
                'key' => 'weekly_work_hours',
                'value' => '44', // As of recent changes, transitioning to 40, but base is typically 44/45 -> 40
                'label' => 'Jornada Laboral (Horas/Semana)',
                'description' => 'Horas de trabajo semanales legales.'
            ],
            [
                'key' => 'legal_gratification_percent',
                'value' => '25',
                'label' => 'Gratificación Legal (%)',
                'description' => 'Porcentaje de gratificación legal anual.'
            ],

            // Default Contribution Rates (%)
            [
                'key' => 'default_afp_rate',
                'value' => '10.0',
                'label' => 'Tasa Base AFP (%)',
                'description' => 'Porcentaje base de cotización obligatoria para pensión (sin comisión).'
            ],
            [
                'key' => 'health_insurance_rate',
                'value' => '7.0',
                'label' => 'Cotización Salud (%)',
                'description' => 'Porcentaje legal para salud (Fonasa/Isapre).'
            ],
            [
                'key' => 'sis_rate',
                'value' => '1.41',
                'label' => 'SIS (%)',
                'description' => 'Seguro de Invalidez y Sobrevivencia.'
            ],
            [
                'key' => 'unemployment_insurance_rate_indefinite',
                'value' => '0.6',
                'label' => 'Seguro Cesantía Indefinido (Trabajador %)',
                'description' => 'Aporte del trabajador para contrato indefinido.'
            ],
             [
                'key' => 'mutual_security_rate_min',
                'value' => '0.90', // Often 0.9 + 0.03 etc.
                'label' => 'Tasa Mínima Mutual (%)',
                'description' => 'Tasa básica de cotización de accidentes del trabajo.'
            ],
        ];

        DB::table('legal_parameters')->insert(array_map(function($item) {
            $item['created_at'] = now();
            $item['updated_at'] = now();
            return $item;
        }, $defaults));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_parameters');
    }
};
