<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Datos personales adicionales
            $table->date('birth_date')->nullable()->after('rut');
            $table->string('nationality', 100)->nullable()->after('birth_date');
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed', 'other'])->nullable()->after('nationality');
            $table->integer('num_dependents')->default(0)->after('marital_status');
            
            // Datos laborales
            $table->string('work_schedule')->nullable()->after('contract_type');
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete()->after('company_id');
            
            // Previsión adicional
            $table->decimal('health_contribution', 10, 2)->nullable()->after('isapre_id')->comment('Monto adicional de salud');
            $table->decimal('apv_amount', 10, 2)->nullable()->after('health_contribution')->comment('Ahorro Previsional Voluntario');
            
            // Contacto de emergencia
            $table->string('emergency_contact_name')->nullable()->after('phone');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date',
                'nationality',
                'marital_status',
                'num_dependents',
                'work_schedule',
                'cost_center_id',
                'health_contribution',
                'apv_amount',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });
    }
};
