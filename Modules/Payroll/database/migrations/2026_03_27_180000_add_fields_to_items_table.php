<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->enum('calculation_type', ['fijo', 'proporcional_ausencia', 'liquido'])
                ->default('fijo')
                ->after('type');

            $table->enum('assignment_type', ['igual_para_todos', 'distinto_por_persona'])
                ->default('distinto_por_persona')
                ->after('calculation_type');

            $table->enum('currency', ['CLP', 'UF', 'UTM'])
                ->default('CLP')
                ->after('assignment_type');

            $table->decimal('default_amount', 12, 2)
                ->nullable()
                ->after('currency');

            $table->boolean('is_overtime_base')
                ->default(false)
                ->after('is_gratification_base');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'calculation_type',
                'assignment_type',
                'currency',
                'default_amount',
                'is_overtime_base',
            ]);
        });
    }
};
