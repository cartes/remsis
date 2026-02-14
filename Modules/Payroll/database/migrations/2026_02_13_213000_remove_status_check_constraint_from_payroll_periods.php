<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the check constraint that conflicts with new status values (draft, open, etc.)
        // The error explicitly mentions "payroll_periods_status_check" which is typical for Postgres enums or check constraints.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE payroll_periods DROP CONSTRAINT IF EXISTS payroll_periods_status_check');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably restore the constraint without knowing the exact original definition
        // and it was likely legacy anyway.
    }
};
