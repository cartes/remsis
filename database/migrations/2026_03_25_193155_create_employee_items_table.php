<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('unit', ['CLP', 'UF', 'UTM', 'PERCENTAGE'])->default('CLP');
            $table->enum('periodicity', ['fixed', 'variable'])->default('fixed');
            $table->unsignedInteger('total_installments')->nullable();
            $table->unsignedInteger('current_installment')->nullable()->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Migrar meal_allowance y mobility_allowance existentes a employee_items
        $this->migrateAllowancesToItems();

        // Eliminar columnas de la tabla employees
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['meal_allowance', 'mobility_allowance']);
        });
    }

    private function migrateAllowancesToItems(): void
    {
        // Obtener todas las empresas que tienen empleados con allowances
        $employees = DB::table('employees')
            ->select('id', 'company_id', 'meal_allowance', 'mobility_allowance')
            ->where(function ($q) {
                $q->where('meal_allowance', '>', 0)
                  ->orWhere('mobility_allowance', '>', 0);
            })
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $now = now();
        $companyItemsMap = []; // cache: company_id -> ['colacion' => item_id, 'movilizacion' => item_id]

        foreach ($employees as $employee) {
            $companyId = $employee->company_id;

            // Crear ítems de catálogo para la empresa si no existen aún
            if (!isset($companyItemsMap[$companyId])) {
                $companyItemsMap[$companyId] = [];

                $colacionId = DB::table('items')->insertGetId([
                    'company_id'            => $companyId,
                    'name'                  => 'Colación',
                    'code'                  => 'COLACION',
                    'type'                  => 'haber_no_imponible',
                    'is_taxable'            => false,
                    'is_gratification_base' => false,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);
                $companyItemsMap[$companyId]['colacion'] = $colacionId;

                $movilizacionId = DB::table('items')->insertGetId([
                    'company_id'            => $companyId,
                    'name'                  => 'Movilización',
                    'code'                  => 'MOVILIZACION',
                    'type'                  => 'haber_no_imponible',
                    'is_taxable'            => false,
                    'is_gratification_base' => false,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);
                $companyItemsMap[$companyId]['movilizacion'] = $movilizacionId;
            }

            // Crear employee_item para colación si tiene monto
            if ($employee->meal_allowance > 0) {
                DB::table('employee_items')->insert([
                    'employee_id' => $employee->id,
                    'item_id'     => $companyItemsMap[$companyId]['colacion'],
                    'amount'      => $employee->meal_allowance,
                    'unit'        => 'CLP',
                    'periodicity' => 'fixed',
                    'is_active'   => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }

            // Crear employee_item para movilización si tiene monto
            if ($employee->mobility_allowance > 0) {
                DB::table('employee_items')->insert([
                    'employee_id' => $employee->id,
                    'item_id'     => $companyItemsMap[$companyId]['movilizacion'],
                    'amount'      => $employee->mobility_allowance,
                    'unit'        => 'CLP',
                    'periodicity' => 'fixed',
                    'is_active'   => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Restaurar columnas en employees
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('meal_allowance', 10, 2)->default(0)->after('mobility_allowance')->nullable();
            $table->decimal('mobility_allowance', 10, 2)->default(0)->nullable();
        });

        Schema::dropIfExists('employee_items');
        Schema::dropIfExists('items');
    }
};
