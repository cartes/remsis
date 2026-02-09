<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\AdminPanel\Models\Mutual;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mutuales = ['ACHS', 'MUSEG', 'IST', 'ISL', 'Sin Mutual'];

        foreach ($mutuales as $nombre) {
            if (Mutual::where('nombre', $nombre)->doesntExist()) {
                Mutual::create(['nombre' => $nombre]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No borramos datos en rollback para proteger integridad referencial
    }
};
