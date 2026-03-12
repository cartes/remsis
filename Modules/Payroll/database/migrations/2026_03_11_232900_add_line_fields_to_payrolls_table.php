<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('payrolls', 'overtime_amount')) {
                $table->decimal('overtime_amount', 10, 2)->default(0)->after('overtime_hours');
            }

            if (! Schema::hasColumn('payrolls', 'gratification_amount')) {
                $table->decimal('gratification_amount', 10, 2)->default(0)->after('base_salary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('payrolls', 'overtime_amount')) {
                $dropColumns[] = 'overtime_amount';
            }

            if (Schema::hasColumn('payrolls', 'gratification_amount')) {
                $dropColumns[] = 'gratification_amount';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
