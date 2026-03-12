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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('work_schedule_type')->nullable()->after('work_schedule');
            $table->decimal('part_time_hours', 5, 2)->nullable()->after('work_schedule_type');
            $table->json('part_time_schedule')->nullable()->after('part_time_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['work_schedule_type', 'part_time_hours', 'part_time_schedule']);
        });
    }
};
