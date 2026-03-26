<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('title');
            $table->enum('document_type', ['contrato', 'anexo', 'comprobante', 'legal', 'otro'])->default('otro');
            $table->string('file_path');
            $table->enum('signature_status', [
                'sin_firma',
                'pendiente_colaborador',
                'pendiente_empresa',
                'firmado_completamente',
            ])->default('sin_firma');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
