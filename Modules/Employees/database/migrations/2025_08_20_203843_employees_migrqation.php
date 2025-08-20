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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // FK a users (usa la tabla real `users`)
            $table->foreignId('user_id')
                ->constrained('users')           // => App\Models\User por defecto
                ->cascadeOnDelete();

            // FK a companies (usa tu módulo Companies)
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')       // tabla companies
                ->nullOnDelete();

            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('position')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->date('hire_date')->nullable();

            // CCAF / AFP / ISAPRE si esas tablas existen:
            $table->foreignId('ccaf_id')->nullable()->constrained('ccafs')->nullOnDelete();
            $table->foreignId('isapre_id')->nullable()->constrained('isapres')->nullOnDelete();
            $table->foreignId('afp_id')->nullable()->constrained('afps')->nullOnDelete();

            $table->timestamps();

            // 1 usuario -> 1 employee (estilo “pivote enriquecido”)
            $table->unique('user_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
