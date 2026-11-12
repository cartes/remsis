<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 2:
     * 1. Crea la tabla `employment_contracts` para gestionar contratos e historial contractual de colaboradores.
     * 2. Añade `tenant_id` a `employees`, `items` y `employee_items`.
     * 3. Añade banderas normativas LRE (`is_tributable`, `is_taxable_afc`, `is_severance_base`) a `items`.
     * 4. Genera contratos activos iniciales para empleados preexistentes.
     */
    public function up(): void
    {
        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'tenant_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();
                $table->index('tenant_id');
            });
        }

        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                if (! Schema::hasColumn('items', 'tenant_id')) {
                    $table->foreignId('tenant_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('tenants')
                        ->nullOnDelete();
                    $table->index('tenant_id');
                }

                if (! Schema::hasColumn('items', 'is_tributable')) {
                    $table->boolean('is_tributable')->default(true)->after('is_taxable');
                }

                if (! Schema::hasColumn('items', 'is_taxable_afc')) {
                    $table->boolean('is_taxable_afc')->default(true)->after('is_tributable');
                }

                if (! Schema::hasColumn('items', 'is_severance_base')) {
                    $table->boolean('is_severance_base')->default(true)->after('is_overtime_base');
                }
            });
        }

        if (Schema::hasTable('employee_items') && ! Schema::hasColumn('employee_items', 'tenant_id')) {
            Schema::table('employee_items', function (Blueprint $table) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();
                $table->index('tenant_id');
            });
        }

        Schema::create('employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->nullOnDelete();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->foreignId('cost_center_id')
                ->nullable()
                ->constrained('cost_centers')
                ->nullOnDelete();

            $table->string('contract_number', 50)->nullable();
            $table->string('contract_type', 30)->default('indefinido')->index(); // indefinido, plazo_fijo, por_obra, honorarios
            $table->string('position', 150)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Sueldo base pactado en CLP redondeado a 0 decimales (Art. 42 letra a CT)
            $table->decimal('base_salary', 12, 0)->default(0);
            $table->string('salary_type', 30)->default('mensual');
            $table->string('gratification_type', 30)->default('art_50'); // art_50, art_47, convencional, sin_gratificacion

            // Jornada ordinaria semanal (Ley 21.561 "40 Horas": 42h en 2026, 40h en 2028)
            $table->unsignedTinyInteger('weekly_hours')->default(42);
            $table->string('work_schedule_type', 30)->default('full_time'); // full_time, part_time, art_22
            $table->unsignedTinyInteger('distribution_days')->default(5);
            $table->boolean('is_afc_exempt')->default(false);

            $table->string('status', 20)->default('active')->index(); // active, terminated, expired, draft
            $table->date('termination_date')->nullable();
            $table->string('termination_cause_article', 60)->nullable(); // Ej: Art. 159 N° 4, Art. 160, Art. 161 CT
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'employee_id', 'status']);
        });

        // Backfill de tenant_id y creación de contrato activo para empleados ya registrados
        if (Schema::hasTable('employees') && Schema::hasTable('companies')) {
            $employees = DB::table('employees')->get();

            foreach ($employees as $emp) {
                $company = $emp->company_id
                    ? DB::table('companies')->where('id', $emp->company_id)->first()
                    : null;

                $tenantId = $company->tenant_id ?? null;

                if ($tenantId && empty($emp->tenant_id)) {
                    DB::table('employees')->where('id', $emp->id)->update(['tenant_id' => $tenantId]);
                }

                if ($emp->company_id) {
                    $hasContract = DB::table('employment_contracts')
                        ->where('employee_id', $emp->id)
                        ->exists();

                    if (! $hasContract) {
                        $weeklyHours = (int) ($emp->part_time_hours ?: ($company->weekly_hours ?? 42));
                        if ($weeklyHours <= 0) {
                            $weeklyHours = 42;
                        }

                        DB::table('employment_contracts')->insert([
                            'tenant_id' => $tenantId,
                            'company_id' => $emp->company_id,
                            'employee_id' => $emp->id,
                            'cost_center_id' => $emp->cost_center_id ?? null,
                            'contract_number' => sprintf('CTR-%d-%04d', $emp->company_id, $emp->id),
                            'contract_type' => $emp->contract_type ?: 'indefinido',
                            'position' => $emp->position ?? null,
                            'start_date' => $emp->hire_date ?: now()->toDateString(),
                            'end_date' => null,
                            'base_salary' => round((float) ($emp->salary ?? 0), 0),
                            'salary_type' => $emp->salary_type ?: 'mensual',
                            'gratification_type' => $company->gratification_system ?? 'art_50',
                            'weekly_hours' => $weeklyHours,
                            'work_schedule_type' => $emp->work_schedule_type ?: 'full_time',
                            'distribution_days' => 5,
                            'is_afc_exempt' => false,
                            'status' => ($emp->status ?? 'active') === 'active' ? 'active' : 'terminated',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employment_contracts');

        if (Schema::hasTable('employee_items') && Schema::hasColumn('employee_items', 'tenant_id')) {
            Schema::table('employee_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }

        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                if (Schema::hasColumn('items', 'tenant_id')) {
                    $table->dropConstrainedForeignId('tenant_id');
                }
                $dropCols = array_filter(
                    ['is_tributable', 'is_taxable_afc', 'is_severance_base'],
                    static fn (string $col): bool => Schema::hasColumn('items', $col)
                );
                if (! empty($dropCols)) {
                    $table->dropColumn($dropCols);
                }
            });
        }

        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'tenant_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};

