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
        // Table for tracking employee balances for each cycle
        Schema::create('employee_administrative_day_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('policy_id')->nullable(); // Reference only, not hard constraint to allow soft delete/policy reset
            
            $table->string('cycle_key');
            $table->date('cycle_starts_on');
            $table->date('cycle_ends_on');
            
            // Policy snapshots
            $table->decimal('policy_snapshot_days', 5, 2);
            $table->string('policy_snapshot_unit');
            $table->string('policy_snapshot_cycle');

            // Balance units
            $table->decimal('assigned_units', 5, 2);
            $table->decimal('used_units', 5, 2)->default(0);
            $table->decimal('available_units', 5, 2);
            
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'cycle_key'], 'emp_policy_cycle_unique');
        });

        // Table for tracking usages of administrative days linked to attendance records
        Schema::create('administrative_day_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->foreignId('attendance_record_id')
                ->unique()
                ->constrained('attendance_records')
                ->cascadeOnDelete();
            $table->foreignId('balance_id')
                ->constrained('employee_administrative_day_balances')
                ->cascadeOnDelete();
                
            $table->decimal('used_units', 5, 2);
            $table->date('used_on');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrative_day_usages');
        Schema::dropIfExists('employee_administrative_day_balances');
    }
};
