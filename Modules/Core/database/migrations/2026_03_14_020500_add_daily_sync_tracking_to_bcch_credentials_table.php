<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bcch_credentials', function (Blueprint $table) {
            $table->date('last_daily_sync_attempted_for')->nullable()->after('password');
            $table->timestamp('last_daily_sync_attempted_at')->nullable()->after('last_daily_sync_attempted_for');
            $table->timestamp('last_daily_sync_succeeded_at')->nullable()->after('last_daily_sync_attempted_at');
            $table->text('last_daily_sync_error')->nullable()->after('last_daily_sync_succeeded_at');
        });
    }

    public function down(): void
    {
        Schema::table('bcch_credentials', function (Blueprint $table) {
            $table->dropColumn([
                'last_daily_sync_attempted_for',
                'last_daily_sync_attempted_at',
                'last_daily_sync_succeeded_at',
                'last_daily_sync_error',
            ]);
        });
    }
};
