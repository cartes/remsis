<?php

namespace Modules\AdminPanel\Database\Seeders;

use Illuminate\Database\Seeder;

use Modules\AdminPanel\Models\TaxableItem;

class TaxableItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['code' => 'HE01', 'name' => 'Sobresueldo (Horas Extras)', 'description' => 'Pago por horas extras trabajadas.', 'is_active' => true],
            ['code' => 'CO01', 'name' => 'Comisiones', 'description' => 'Remuneración variable por ventas u objetivos.', 'is_active' => true],
            ['code' => 'SC01', 'name' => 'Semana Corrida', 'description' => 'Pago del 7mo día por remuneraciones variables.', 'is_active' => true],
            ['code' => 'BI01', 'name' => 'Bonos e Incentivos', 'description' => 'Bonos imponibles por metas, responsabilidad, etc.', 'is_active' => true],
            ['code' => 'AG01', 'name' => 'Aguinaldos', 'description' => 'Aguinaldo imponible de fiestas patrias, navidad, etc.', 'is_active' => true],
        ];

        foreach ($items as $item) {
            TaxableItem::firstOrCreate(['code' => $item['code']], $item);
        }
    }
}
