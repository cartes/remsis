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
        Schema::table('payroll_periods', function (Blueprint $table) {
            // Add company relationship
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
            
            // Add year and month fields for better period tracking
            $table->integer('year')->after('name');
            $table->integer('month')->after('year'); // 1-12
            
            // Add notes field
            $table->text('notes')->nullable()->after('status');
            
            // Add soft deletes
            $table->softDeletes();
            
            // Add unique constraint to prevent duplicate periods per company
            $table->unique(['company_id', 'year', 'month'], 'unique_company_period');
        });
        
        // Note: If you need to change existing status values from 'active/closed/cancelled' 
        // to 'draft/open/closed/paid', you should do this manually or with a data migration.
        // SQLite doesn't support modifying enum columns, and this migration is designed to work
        // with both MySQL and SQLite.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            // Drop unique constraint first
            $table->dropUnique('unique_company_period');
            
            // Drop added columns
            $table->dropSoftDeletes();
            $table->dropColumn(['company_id', 'year', 'month', 'notes']);
        });
    }
};
