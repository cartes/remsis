<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feriado anual (Arts. 67 a 73 del Código del Trabajo).
 *
 * - companies.vacation_days_per_year: días hábiles por año de servicio (15; 20 en zonas extremas).
 * - employees.vacation_initial_balance(_date): saldo de apertura al empezar a usar el sistema.
 * - employees.previous_employment_months: meses acreditados con otros empleadores para el
 *   feriado progresivo (Art. 68), con tope legal de 10 años.
 * - vacation_requests: solicitudes y registros de vacaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'vacation_days_per_year')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->decimal('vacation_days_per_year', 4, 1)->default(15);
            });
        }

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'vacation_initial_balance')) {
                $table->decimal('vacation_initial_balance', 6, 2)->nullable();
            }
            if (! Schema::hasColumn('employees', 'vacation_initial_balance_date')) {
                $table->date('vacation_initial_balance_date')->nullable();
            }
            if (! Schema::hasColumn('employees', 'previous_employment_months')) {
                $table->unsignedSmallInteger('previous_employment_months')->default(0);
            }
        });

        if (! Schema::hasTable('vacation_requests')) {
            Schema::create('vacation_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('working_days', 5, 1); // días hábiles descontados del saldo
                $table->string('status', 20)->default('pending')->index(); // pending, approved, rejected, cancelled
                $table->string('origin', 20)->default('employee'); // employee, company
                $table->text('notes')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'start_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_requests');

        Schema::table('employees', function (Blueprint $table) {
            foreach (['vacation_initial_balance', 'vacation_initial_balance_date', 'previous_employment_months'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('companies', 'vacation_days_per_year')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('vacation_days_per_year');
            });
        }
    }
};
