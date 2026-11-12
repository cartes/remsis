<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas globales de indicadores previsionales mensuales de Chile:
     * - monthly_indicators (cabecera mensual + caché JSON para cálculo rápido)
     * - monthly_afp_rates (tasas AFP del período)
     * - monthly_iut_brackets (8 tramos Impuesto Único de Segunda Categoría)
     * - monthly_family_allowance_brackets (tramos A, B, C, D de Asignación Familiar)
     */
    public function up(): void
    {
        Schema::create('monthly_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique(); // Formato YYYY-MM
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedTinyInteger('month')->index();

            // Valores económicos base
            $table->decimal('uf_value', 10, 2);
            $table->decimal('uf_previous_month', 10, 2);
            $table->decimal('utm_value', 10, 2);
            $table->decimal('uta_value', 12, 2);
            $table->decimal('ipc_value', 5, 2)->default(0.00);

            // Ingreso Mínimo Mensual (IMM) según Ley vigente
            $table->decimal('imm_value', 12, 0);
            $table->decimal('imm_under_18_over_65', 12, 0)->default(0);
            $table->decimal('imm_non_remunerational', 12, 0)->default(0);

            // Topes imponibles legales en UF (Superintendencia de Pensiones)
            $table->decimal('cap_afp_uf', 6, 2)->default(84.30);
            $table->decimal('cap_ips_uf', 6, 2)->default(60.00);
            $table->decimal('cap_afc_uf', 6, 2)->default(126.60);

            // Tasas previsionales globales
            $table->decimal('sis_rate', 5, 2)->default(1.49);
            $table->decimal('afc_indefinite_employee_rate', 5, 2)->default(0.60);
            $table->decimal('afc_indefinite_employer_rate', 5, 2)->default(2.40);
            $table->decimal('afc_fixed_employer_rate', 5, 2)->default(3.00);
            $table->decimal('mutual_base_rate', 5, 2)->default(0.90);
            $table->decimal('sanna_rate', 5, 2)->default(0.03);
            $table->decimal('cesantia_pensionados_employer_rate', 5, 2)->default(0.00);
            $table->unsignedTinyInteger('legal_weekly_hours')->default(42); // Ley 40 Horas (42h en 2026)

            // Caché JSON estructurado para evitar consultas N+1 en corridas de nómina masivas
            $table->json('afp_rates')->nullable();
            $table->json('iut_brackets')->nullable();
            $table->json('family_allowance_brackets')->nullable();

            $table->string('source', 30)->default('manual');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('monthly_afp_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_indicator_id')
                ->constrained('monthly_indicators')
                ->cascadeOnDelete();
            $table->string('afp_code', 20);
            $table->string('afp_name', 60);
            $table->decimal('mandatory_rate', 5, 2)->default(10.00);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('total_rate', 5, 2);
            $table->decimal('independent_rate', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['monthly_indicator_id', 'afp_code']);
        });

        Schema::create('monthly_iut_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_indicator_id')
                ->constrained('monthly_indicators')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('bracket_number'); // Tramo 1 al 8
            $table->decimal('from_utm', 10, 4);
            $table->decimal('to_utm', 10, 4)->nullable(); // Null en el tramo superior (más de X UTM)
            $table->decimal('factor', 8, 6);
            $table->decimal('deduction_utm', 10, 4);
            $table->decimal('max_effective_rate', 6, 4)->nullable();
            $table->timestamps();

            $table->unique(['monthly_indicator_id', 'bracket_number']);
        });

        Schema::create('monthly_family_allowance_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_indicator_id')
                ->constrained('monthly_indicators')
                ->cascadeOnDelete();
            $table->char('bracket_letter', 1); // A, B, C, D
            $table->decimal('income_from', 12, 0);
            $table->decimal('income_to', 12, 0)->nullable(); // Null en el tramo D sin tope superior
            $table->decimal('amount_per_dependent', 12, 0);
            $table->timestamps();

            $table->unique(['monthly_indicator_id', 'bracket_letter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_family_allowance_brackets');
        Schema::dropIfExists('monthly_iut_brackets');
        Schema::dropIfExists('monthly_afp_rates');
        Schema::dropIfExists('monthly_indicators');
    }
};

