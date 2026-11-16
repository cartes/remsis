<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Historial contractual: cada fila de employment_contracts pasa a ser una versión de las
 * condiciones pactadas, vigente entre valid_from y valid_until. Un anexo crea una versión nueva
 * y cierra la anterior, de modo que cada mes se liquida con las condiciones que regían entonces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employment_contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('employment_contracts', 'valid_from')) {
                $table->date('valid_from')->nullable()->index();
            }
            if (! Schema::hasColumn('employment_contracts', 'valid_until')) {
                $table->date('valid_until')->nullable();
            }
            if (! Schema::hasColumn('employment_contracts', 'version_type')) {
                $table->string('version_type', 20)->default('contrato'); // contrato, anexo
            }
            if (! Schema::hasColumn('employment_contracts', 'change_reason')) {
                $table->text('change_reason')->nullable();
            }
        });

        // Los contratos existentes rigen desde su fecha de inicio.
        DB::table('employment_contracts')->whereNull('valid_from')->update(['valid_from' => DB::raw('start_date')]);
    }

    public function down(): void
    {
        Schema::table('employment_contracts', function (Blueprint $table) {
            foreach (['valid_from', 'valid_until', 'version_type', 'change_reason'] as $column) {
                if (Schema::hasColumn('employment_contracts', $column)) {
                    if ($column === 'valid_from') {
                        $table->dropIndex(['valid_from']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
