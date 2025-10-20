<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Separar nombre y apellido
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');

            // RUT
            $table->string('rut')->unique()->nullable()->after('last_name');

            // Info bancaria
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete()->after('company_id');
            $table->string('bank_account_number')->nullable()->after('bank_id');
            $table->enum('bank_account_type', ['corriente', 'vista', 'ahorro'])->nullable()->after('bank_account_number');

            // Tipo de salario y contrato
            $table->enum('salary_type', ['mensual', 'quincenal', 'semanal'])->nullable()->after('salary');
            $table->string('contract_type')->nullable()->after('salary_type');

            // Estado
            $table->enum('status', ['active', 'inactive'])->default('active')->after('hire_date');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'rut',
                'bank_id',
                'bank_account_number',
                'bank_account_type',
                'salary_type',
                'contract_type',
                'status',
            ]);
        });
    }
};
