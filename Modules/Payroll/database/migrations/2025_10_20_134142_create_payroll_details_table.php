<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();

            // Relación con Payroll
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();

            // Concepto, monto y tipo
            $table->string('concept');
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['earning', 'deduction']);
            $table->string('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
