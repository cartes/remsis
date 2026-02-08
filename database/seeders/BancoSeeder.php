<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AdminPanel\Models\Banco;

class BancoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bancos = [
            'Banco de Chile',
            'Banco Estado',
            'Banco Santander',
            'Banco BCI',
            'Banco Scotiabank',
            'Banco Itaú',
            'Banco Security',
            'Banco Falabella',
            'Banco Ripley',
            'Banco Consorcio',
            'Banco BICE',
            'Banco Internacional',
            'Banco BTG Pactual',
            'Banco Coopeuch',
            'MACH',
            'Tenpo',
            'Tapp',
        ];

        foreach ($bancos as $nombre) {
            Banco::updateOrCreate(['nombre' => $nombre]);
        }
    }
}
