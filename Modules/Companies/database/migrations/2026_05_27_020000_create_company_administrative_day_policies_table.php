<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_administrative_day_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->unique()
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->decimal('days_per_cycle', 5, 2)->default(0);
            $table->enum('unit', ['full_day', 'half_day'])->default('full_day');
            $table->enum('cycle_type', ['annual', 'semiannual'])->default('annual');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_administrative_day_policies');
    }
};
