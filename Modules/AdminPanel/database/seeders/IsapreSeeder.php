<?php

namespace Modules\AdminPanel\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AdminPanel\Models\Isapre;

class IsapreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $isapres = [
            'Banmédica',
            'Colmena',
            'Cruz Blanca',
            'Consalud',
            'Nueva Masvida',
            'Vida Tres',
            'Fonasa', // opcional, si quieres incluirlo aquí
        ];

        foreach ($isapres as $nombre) {
            Isapre::firstOrCreate(['nombre' => $nombre]);
        }
    }
}