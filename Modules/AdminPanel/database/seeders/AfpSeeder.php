<?php

namespace Modules\AdminPanel\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AdminPanel\Models\Afp;

class AfpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $afps = [
            'AFP Habitat',
            'AFP Provida',
            'AFP Cuprum',
            'AFP PlanVital',
            'AFP Modelo',
            'AFP Uno',
        ];

        foreach ($afps as $nombre) {
            Afp::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
