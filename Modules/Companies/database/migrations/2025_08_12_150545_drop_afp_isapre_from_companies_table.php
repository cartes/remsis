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
        Schema::table('companies', function (Blueprint $table) {
            // Si existen FKs, dropearlas de forma segura
            if (Schema::hasColumn('companies', 'isapre_id')) {
                $table->dropConstrainedForeignId('isapre_id');
            }
            if (Schema::hasColumn('companies', 'afp_id')) {
                $table->dropConstrainedForeignId('afp_id');
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Solo las agregues si de verdad las necesitas de vuelta
            $table->foreignId('isapre_id')->nullable()->constrained('isapres')->nullOnDelete();
            $table->foreignId('afp_id')->nullable()->constrained('afps')->nullOnDelete();
        });
    }
};
