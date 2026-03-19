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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->integer('meal_allowance')->default(0)->after('gross_salary');
            $table->integer('mobility_allowance')->default(0)->after('meal_allowance');
            $table->integer('non_taxable_earnings')->default(0)->after('mobility_allowance');
            $table->integer('total_earnings')->default(0)->after('non_taxable_earnings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'meal_allowance',
                'mobility_allowance',
                'non_taxable_earnings',
                'total_earnings',
            ]);
        });
    }
};
