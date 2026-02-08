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
        Schema::create('codigos_sii', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('glosa');
            $table->decimal('utms_min', 8, 2)->default(13.5);
            $table->string('categoria', 10)->default('2da');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigos_sii');
    }
};
