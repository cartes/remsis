<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Licencias médicas (DS 3 de 1984) y estimación del subsidio por incapacidad laboral (DFL 44 de 1978).
 *
 * - medical_leaves: una fila por formulario de licencia, con su resolución y entidad pagadora.
 *   `continues_leave_id` enlaza una licencia con la anterior cuando es continuación sin
 *   interrupción por el mismo diagnóstico; la carencia se determina sobre la suma de ambas.
 * - payrolls.medical_leave_days: días de licencia del mes, base 30, por los que el empleador
 *   sigue enterando el SIS y su parte del seguro de cesantía.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('medical_leaves')) {
            Schema::create('medical_leaves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('continues_leave_id')->nullable()->constrained('medical_leaves')->nullOnDelete();
                $table->string('folio', 30);
                $table->string('type', 30);
                $table->date('start_date');
                $table->date('end_date');
                $table->unsignedSmallInteger('days'); // días corridos prescritos
                $table->string('status', 20)->default('in_process')->index(); // in_process, authorized, reduced, rejected, cancelled
                $table->unsignedSmallInteger('authorized_days')->nullable(); // sólo cuando se reduce
                $table->date('resolution_date')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->string('payer_type', 20); // compin, ccaf, isapre, mutual, isl
                $table->string('payer_name', 150)->nullable();
                $table->decimal('subsidy_base_amount', 12, 2)->nullable(); // remuneración neta mensual promedio
                $table->decimal('subsidy_daily_amount', 12, 2)->nullable();
                $table->unsignedSmallInteger('subsidy_days')->default(0); // días con derecho a subsidio, descontada la carencia
                $table->decimal('subsidy_amount', 12, 2)->nullable();
                $table->json('subsidy_detail')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['employee_id', 'start_date']);
                $table->index(['company_id', 'folio']);
            });
        }

        if (! Schema::hasColumn('payrolls', 'medical_leave_days')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->unsignedSmallInteger('medical_leave_days')->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payrolls', 'medical_leave_days')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->dropColumn('medical_leave_days');
            });
        }

        Schema::dropIfExists('medical_leaves');
    }
};
