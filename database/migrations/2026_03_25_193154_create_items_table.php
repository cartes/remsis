<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name', 150);
            $table->string('code', 50)->nullable();
            $table->enum('type', [
                'haber_imponible',
                'haber_no_imponible',
                'descuento_legal',
                'descuento_varios',
                'credito',
            ]);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_gratification_base')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
