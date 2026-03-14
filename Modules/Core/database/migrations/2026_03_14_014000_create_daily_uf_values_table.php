<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_uf_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_ipc_value_id')->constrained('monthly_ipc_values')->cascadeOnDelete();
            $table->date('date')->unique();
            $table->date('cycle_start_date');
            $table->date('cycle_end_date');
            $table->decimal('value', 12, 2);
            $table->timestamps();

            $table->index(['cycle_start_date', 'cycle_end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_uf_values');
    }
};
