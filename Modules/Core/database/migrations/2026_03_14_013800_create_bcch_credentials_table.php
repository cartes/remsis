<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bcch_credentials', function (Blueprint $table) {
            $table->id();
            $table->text('username');
            $table->text('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bcch_credentials');
    }
};
