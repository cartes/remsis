<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula las razones sociales (companies) y usuarios (users) a la entidad Tenant,
     * asegurando un tenant por defecto para registros preexistentes.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'tenant_id')) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();
                $table->index('tenant_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tenant_id')) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();
                $table->index('tenant_id');
            }
        });

        // Si existen empresas previas sin tenant_id, creamos y asignamos un Tenant por defecto
        if (Schema::hasTable('tenants') && DB::table('companies')->whereNull('tenant_id')->exists()) {
            $defaultTenantId = DB::table('tenants')->where('slug', 'default')->value('id');

            if (! $defaultTenantId) {
                $defaultTenantId = DB::table('tenants')->insertGetId([
                    'name' => 'Default Organization',
                    'slug' => 'default',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('companies')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
            DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tenant_id')) {
                $table->dropConstrainedForeignId('tenant_id');
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'tenant_id')) {
                $table->dropConstrainedForeignId('tenant_id');
            }
        });
    }
};

