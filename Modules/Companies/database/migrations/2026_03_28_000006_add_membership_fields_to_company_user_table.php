<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extiende la tabla pivote company_user para soportar membresías contextuales
     * por tenant/empresa, delegación de funciones (outsourcing) y trazabilidad.
     */
    public function up(): void
    {
        if (! Schema::hasTable('company_user')) {
            return;
        }

        Schema::table('company_user', function (Blueprint $table) {
            if (! Schema::hasColumn('company_user', 'role')) {
                $table->string('role', 50)->nullable()->after('company_id');
            }

            if (! Schema::hasColumn('company_user', 'permissions')) {
                $table->json('permissions')->nullable()->after('role');
            }

            if (! Schema::hasColumn('company_user', 'is_external')) {
                $table->boolean('is_external')->default(false)->after('permissions');
            }

            if (! Schema::hasColumn('company_user', 'delegated_by')) {
                $table->foreignId('delegated_by')
                    ->nullable()
                    ->after('is_external')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('company_user')) {
            return;
        }

        Schema::table('company_user', function (Blueprint $table) {
            if (Schema::hasColumn('company_user', 'delegated_by')) {
                $table->dropConstrainedForeignId('delegated_by');
            }

            foreach (['is_external', 'permissions', 'role'] as $column) {
                if (Schema::hasColumn('company_user', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

