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
        Schema::create('administrative_day_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->date('requested_date');
            
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])
                ->default('pending');
                
            $table->text('notes')->nullable();
            
            // Approval/rejection metadata
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();

            $table->unique(['employee_id', 'requested_date'], 'emp_req_date_unique');
        });

        Schema::table('administrative_day_usages', function (Blueprint $table) {
            $table->foreignId('request_id')
                ->nullable()
                ->after('balance_id')
                ->constrained('administrative_day_requests')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('administrative_day_usages', function (Blueprint $table) {
            $table->dropForeign(['request_id']);
            $table->dropColumn('request_id');
        });

        Schema::dropIfExists('administrative_day_requests');
    }
};
