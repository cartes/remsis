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
            $table->foreignId('ccaf_id')->nullable()->constrained('ccafs')->nullOnDelete();
            $table->foreignId('isapre_id')->nullable()->constrained('isapre')->nullOnDelete();
            $table->foreignId('afp_id')->nullable()->constrained('afp')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ccaf_id');
            $table->dropConstrainedForeignId('isapre_id');
            $table->dropConstrainedForeignId('afp_id');
        });
    }
};
