<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->date('date');
            $table->string('scheduled_shift')->nullable();
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->integer('overtime_minutes')->default(0);
            $table->integer('delay_minutes')->default(0);
            $table->enum('status', ['asistio', 'ausente', 'atraso', 'licencia', 'vacaciones'])
                ->default('asistio');
            $table->timestamps();

            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
