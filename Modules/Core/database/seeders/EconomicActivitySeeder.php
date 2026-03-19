<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\EconomicActivity;

class EconomicActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Cargando códigos de actividad económica desde archivo local...');

        // Usamos la ruta local y empaquetada junto al seeder para que funcione siempre en producción
        $jsonPath = __DIR__.'/data/sii_codes.json';

        if (! file_exists($jsonPath)) {
            $this->command->error("No se encontró el archivo JSON en: {$jsonPath}");
            $this->command->info('Asegúrate de haber corrido primero el scraper de Python u obtener el sii_codes.json.');

            return;
        }

        try {
            $json = file_get_contents($jsonPath);
            $activities = json_decode($json, true);

            if (! is_array($activities) || empty($activities)) {
                $this->command->error('El archivo JSON está vacío o no es válido.');

                return;
            }

            $count = count($activities);
            $this->command->info("Se encontraron {$count} códigos en el archivo. Guardando en la base de datos...");

            $bar = $this->command->getOutput()->createProgressBar($count);
            $bar->start();

            foreach ($activities as $activity) {
                EconomicActivity::updateOrCreate(
                    ['code' => $activity['code']],
                    [
                        'name' => mb_strtoupper((string) $activity['name'], 'UTF-8'),
                        'category' => (string) $activity['category'],
                    ]
                );
                $bar->advance();
            }

            $bar->finish();
            $this->command->info("\n¡Semilla de actividades económicas finalizada con éxito!");

        } catch (\Exception $e) {
            $this->command->error('Ocurrió un error al procesar el JSON: '.$e->getMessage());
        }
    }
}
