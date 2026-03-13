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
        Schema::create('freelancers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('rut')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('profession')->nullable();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->onDelete('set null');
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_type')->nullable();
            $table->decimal('default_gross_fee', 10, 2)->nullable();
            $table->decimal('default_retention_rate', 5, 2)->default(13.75);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelancers');
    }
};
