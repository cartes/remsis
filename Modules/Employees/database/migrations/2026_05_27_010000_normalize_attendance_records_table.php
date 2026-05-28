<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Limpieza de duplicados de marcas en la misma fecha para el mismo colaborador
        $duplicates = DB::table('attendance_records')
            ->select('employee_id', 'date', DB::raw('COUNT(*) as count'))
            ->groupBy('employee_id', 'date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            // Mantener el registro más reciente según updated_at / id
            $latest = DB::table('attendance_records')
                ->where('employee_id', $duplicate->employee_id)
                ->where('date', $duplicate->date)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            if ($latest) {
                DB::table('attendance_records')
                    ->where('employee_id', $duplicate->employee_id)
                    ->where('date', $duplicate->date)
                    ->where('id', '!=', $latest->id)
                    ->delete();
            }
        }

        // 2. Agregar restricción UNIQUE
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unique(['employee_id', 'date'], 'employee_date_unique');
        });

        // 3. Normalizar valores históricos de justification_type al contrato canónico
        DB::table('attendance_records')
            ->where('justification_type', 'unpaid_leave')
            ->update(['justification_type' => 'permiso_sin_goce']);

        DB::table('attendance_records')
            ->where('justification_type', 'work_accident')
            ->update(['justification_type' => 'accidente_trabajo']);

        DB::table('attendance_records')
            ->where('justification_type', 'suspension')
            ->update(['justification_type' => 'suspension_disciplinaria']);

        DB::table('attendance_records')
            ->where('justification_type', 'unjustified')
            ->update(['justification_type' => 'inasistencia_injustificada']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropUnique('employee_date_unique');
        });
    }
};
