<?php

declare(strict_types=1);

use App\Rules\Rut;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Vincula 1:1 cada empresa (Company) a su propio Tenant (ADR-001: 1 RUT = 1 empresa = 1 tenant),
     * y asocia usuarios preexistentes al tenant de su empresa.
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
                $table->unique('tenant_id');
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

        // Si existen empresas previas sin tenant_id, creamos un Tenant 1:1 exclusivo por cada empresa
        if (Schema::hasTable('tenants') && DB::table('companies')->whereNull('tenant_id')->exists()) {
            $companies = DB::table('companies')->whereNull('tenant_id')->orderBy('id')->get();

            foreach ($companies as $company) {
                $name = trim((string) ($company->razon_social ?: $company->name ?: "Empresa {$company->id}"));
                $normalizedRut = Rut::normalize($company->rut ?? null);
                $baseSlug = Str::slug((string) ($company->slug ?? $name)) ?: "empresa-{$company->id}";
                $slug = $baseSlug;
                $suffix = 1;

                while (DB::table('tenants')->where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$company->id}".($suffix > 1 ? "-{$suffix}" : '');
                    $suffix++;
                }

                // Si ya existía un tenant huérfano con el mismo RUT, reutilizarlo solo si no tiene otra empresa asociada
                $existingTenant = $normalizedRut !== null
                    ? DB::table('tenants')->where('rut', $normalizedRut)->first()
                    : null;

                if ($existingTenant && ! DB::table('companies')->where('tenant_id', $existingTenant->id)->exists()) {
                    $tenantId = (int) $existingTenant->id;
                } else {
                    $tenantRut = ($normalizedRut !== null && ! DB::table('tenants')->where('rut', $normalizedRut)->exists())
                        ? $normalizedRut
                        : null;

                    $tenantId = (int) DB::table('tenants')->insertGetId([
                        'name' => $name,
                        'rut' => $tenantRut,
                        'slug' => $slug,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('companies')->where('id', $company->id)->update([
                    'tenant_id' => $tenantId,
                    'rut' => $normalizedRut ?? $company->rut,
                ]);

                if (Schema::hasColumn('users', 'company_id')) {
                    DB::table('users')
                        ->where('company_id', $company->id)
                        ->whereNull('tenant_id')
                        ->update(['tenant_id' => $tenantId]);
                }
            }
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

