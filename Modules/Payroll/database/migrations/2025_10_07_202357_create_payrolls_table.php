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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('payroll_period_id')->nullable()->constrained('payroll_periods');

            $table->foreignId('afp_id')->nullable()->constrained('afps');
            $table->decimal('afp_amount', 10, 2)->default(0);
            $table->foreignId('isapre_id')->nullable()->constrained('isapres');
            $table->decimal('isapre_amount', 10, 2)->default(0);
            $table->foreignId('ccaf_id')->nullable()->constrained('ccafs');
            $table->decimal('ccaf_amount', 10, 2)->default(0);

            $table->decimal('cesantia_amount', 10, 2)->default(0);
            $table->decimal('impuesto_unico_amount', 10, 2)->default(0);
            $table->decimal('anticipos_amount', 10, 2)->default(0);
            $table->decimal('otros_descuentos', 10, 2)->default(0);

            $table->integer('period_year');
            $table->integer('period_month');
            $table->integer('worked_days')->default(30);
            $table->decimal('overtime_hours', 8, 2)->default(0);

            $table->decimal('base_salary', 10, 2);
            $table->decimal('gross_salary', 10, 2);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2);

            $table->date('payment_date')->nullable();
            $table->enum('status', ['pending', 'processed', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();

            // Información bancaria
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->enum('bank_account_type', ['corriente', 'vista', 'ahorro'])->nullable();

            $table->timestamps();

            // Índices
            $table->index(['period_year', 'period_month']);
            $table->index(['employee_id', 'period_year', 'period_month']);
            $table->index('status');
            $table->unique(['employee_id', 'period_year', 'period_month'], 'unique_employee_period');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
