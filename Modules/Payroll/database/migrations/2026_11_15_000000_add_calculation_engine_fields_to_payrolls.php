<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos del motor único de cálculo:
 * - separa los descuentos por ítems/créditos del descuento manual (`otros_descuentos`),
 *   para que recalcular no los acumule;
 * - registra APV, asignación familiar, base tributable y costos del empleador;
 * - vincula cada cuota de crédito cobrada con la liquidación (una cuota por período).
 */
return new class extends Migration
{
    private const PAYROLL_COLUMNS = [
        'apv_amount',
        'asignacion_familiar_amount',
        'descuentos_items_amount',
        'creditos_amount',
        'tributable_base',
        'employer_sis_amount',
        'employer_afc_amount',
        'employer_mutual_amount',
    ];

    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            foreach (self::PAYROLL_COLUMNS as $column) {
                if (! Schema::hasColumn('payrolls', $column)) {
                    $table->decimal($column, 12, 2)->default(0);
                }
            }
        });

        Schema::table('payroll_details', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_details', 'employee_item_id')) {
                $table->unsignedBigInteger('employee_item_id')->nullable()->index();
            }
            if (! Schema::hasColumn('payroll_details', 'installment_number')) {
                $table->unsignedInteger('installment_number')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_details', 'employee_item_id')) {
                $table->dropIndex(['employee_item_id']);
                $table->dropColumn('employee_item_id');
            }
            if (Schema::hasColumn('payroll_details', 'installment_number')) {
                $table->dropColumn('installment_number');
            }
        });

        Schema::table('payrolls', function (Blueprint $table) {
            foreach (self::PAYROLL_COLUMNS as $column) {
                if (Schema::hasColumn('payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
