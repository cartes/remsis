<?php

namespace Modules\AdminPanel\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AdminPanel\Models\Ccaf;

class CcafSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $ccafs = [
            'Caja Los Andes',
            'Caja 18 de Septiembre',
            'Caja La Araucana',
            'Caja Los Héroes',
            'Caja Gabriela Mistral',
        ];

        foreach ($ccafs as $nombre) {
            Ccaf::firstOrCreate(['nombre' => $nombre]);
        }
    }

}
