<?php

namespace Modules\AdminPanel\Database\Seeders;

use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $bancos = [
            ['name' => 'Banco de Chile', 'code' => '001'],
            ['name' => 'BancoEstado', 'code' => '012'],
            ['name' => 'Banco Santander', 'code' => '037'],
            ['name' => 'Banco BCI', 'code' => '028'],
            ['name' => 'Banco Itaú', 'code' => '039'],
            ['name' => 'Scotiabank Chile', 'code' => '049'],
            ['name' => 'Banco Falabella', 'code' => '051'],
            ['name' => 'Banco Ripley', 'code' => '053'],
            ['name' => 'Banco Consorcio', 'code' => '055'],
            ['name' => 'Banco Security', 'code' => '057'],
            ['name' => 'Banco Internacional', 'code' => '059'],
            ['name' => 'Banco BICE', 'code' => '060'],
            ['name' => 'Tapp (Caja Los Andes)', 'code' => 'TAPP'],
            ['name' => 'MACH (BCI)', 'code' => 'MACH'],
            ['name' => 'Tenpo', 'code' => 'TENPO'],
            ['name' => 'Global66', 'code' => 'GL66'],
            ['name' => 'Superdigital', 'code' => 'SDCL'],
            ['name' => 'Wise Chile', 'code' => 'WISE'],
            ['name' => 'Chita', 'code' => 'CHITA'],
            ['name' => 'Coopeuch', 'code' => 'COOP'],
            ['name' => 'Caja Los Héroes', 'code' => 'CLH'],
            ['name' => 'Caja La Araucana', 'code' => 'CLA'],
            ['name' => 'Caja 18 de Septiembre', 'code' => 'C18'],
            ['name' => 'Pagos360', 'code' => 'P360'],
            ['name' => 'Fintoc', 'code' => 'FNTC'],
            ['name' => 'Khipu', 'code' => 'KHPU'],
            // Agrega más según tus necesidades
        ];

        foreach ($bancos as $banco) {
            \Modules\AdminPanel\Models\Bank::firstOrCreate($banco);
        }
    }
}
