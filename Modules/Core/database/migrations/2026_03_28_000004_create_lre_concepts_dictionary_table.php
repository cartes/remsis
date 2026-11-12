<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el diccionario global de conceptos del Libro de Remuneraciones Electrónico (LRE)
     * exigido por la Dirección del Trabajo (DT), con banderas normativas según Art. 41, 42 y 58 del Código del Trabajo.
     */
    public function up(): void
    {
        Schema::create('lre_concepts_dictionary', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 150);
            $table->enum('classification', [
                'haber_imponible',
                'haber_no_imponible',
                'descuento_legal',
                'descuento_judicial',
                'descuento_voluntario',
                'aporte_empleador',
            ])->index();
            $table->string('legal_article', 60)->nullable();

            // Banderas normativas del Código del Trabajo (Art. 41, 42 y 58)
            $table->boolean('is_taxable')->default(false); // Imponible AFP y Salud
            $table->boolean('is_taxable_afc')->default(false); // Imponible Seguro de Cesantía
            $table->boolean('is_tributable')->default(false); // Afecto a Impuesto Único (IUSC)
            $table->boolean('is_gratification_base')->default(false); // Base cálculo Gratificación Legal
            $table->boolean('is_overtime_base')->default(false); // Base cálculo Horas Extraordinarias (Art. 32 CT)
            $table->boolean('is_severance_base')->default(false); // Base indemnización años de servicio (Art. 172 CT)

            $table->string('previred_code', 10)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('items') && ! Schema::hasColumn('items', 'lre_concept_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->foreignId('lre_concept_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('lre_concepts_dictionary')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('items') && Schema::hasColumn('items', 'lre_concept_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('lre_concept_id');
            });
        }

        Schema::dropIfExists('lre_concepts_dictionary');
    }
};

