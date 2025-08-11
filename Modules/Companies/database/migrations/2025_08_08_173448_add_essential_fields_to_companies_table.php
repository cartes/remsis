<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Identificación legal
            $table->string('razon_social')->nullable()->after('name');
            $table->string('nombre_fantasia')->nullable()->after('razon_social');
            $table->string('giro')->nullable()->after('rut');

            // Dirección y contacto
            $table->string('direccion')->nullable()->after('giro');
            $table->string('comuna')->nullable()->after('direccion');
            $table->string('region')->nullable()->after('comuna');

            // Datos legales / configuración de remuneraciones
            $table->string('tipo_contribuyente')->nullable()->comment('natural | juridica')->after('region');
            $table->string('ccaf')->nullable()->after('tipo_contribuyente');     // luego lo normalizamos a FK
            $table->string('mutual')->nullable()->after('ccaf');                 // luego lo normalizamos a FK

            // Pago de sueldos
            $table->string('dia_pago')->nullable()->comment('ultimo_dia_habil | dia_fijo | quincenal')->after('mutual');
            $table->tinyInteger('dia_pago_dia')->nullable()->comment('1..31 si dia_fijo')->after('dia_pago');

            // Bancos/Cuentas
            $table->string('banco')->nullable()->after('dia_pago_dia');
            $table->string('cuenta_bancaria')->nullable()->after('banco');

            // Representante legal
            $table->string('representante_nombre')->nullable()->after('cuenta_bancaria');
            $table->string('representante_rut')->nullable()->after('representante_nombre');
            $table->string('representante_cargo')->nullable()->after('representante_rut');
            $table->string('representante_email')->nullable()->after('representante_cargo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'razon_social',
                'nombre_fantasia',
                'giro',
                'direccion',
                'comuna',
                'region',
                'tipo_contribuyente',
                'ccaf',
                'mutual',
                'dia_pago',
                'dia_pago_dia',
                'banco',
                'cuenta_bancaria',
                'representante_nombre',
                'representante_rut',
                'representante_cargo',
                'representante_email',
            ]);
        });
    }
};
