<?php

namespace Modules\AdminPanel\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AdminPanel\Models\Mutual;

class MutualSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mutuales = ['ACHS', 'MUSEG', 'IST', 'ISL', 'Sin Mutual'];

        foreach ($mutuales as $nombre) {
            Mutual::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
