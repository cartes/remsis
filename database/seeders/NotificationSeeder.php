<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AdminPanel\Models\Notification;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notifications = [
            [
                'title' => 'Pago de IVA',
                'message' => 'Vencimiento de declaración y pago de IVA mensual',
                'type' => 'warning',
                'due_date' => Carbon::now()->addDays(5),
            ],
            [
                'title' => 'Pago de Cotizaciones Previsionales',
                'message' => 'Vencimiento de pago de cotizaciones AFP, Salud y otros',
                'type' => 'warning',
                'due_date' => Carbon::now()->addDays(3),
            ],
            [
                'title' => 'Declaración Renta Mensual',
                'message' => 'Declaración de impuestos a la renta de segunda categoría',
                'type' => 'reminder',
                'due_date' => Carbon::now()->addDays(8),
            ],
            [
                'title' => 'Pago Mutual de Seguridad',
                'message' => 'Vencimiento de pago de cotización de accidentes del trabajo',
                'type' => 'reminder',
                'due_date' => Carbon::now()->addDays(10),
            ],
            [
                'title' => 'Actualización UTM',
                'message' => 'Recordatorio para actualizar el valor de la UTM mensual',
                'type' => 'info',
                'due_date' => Carbon::now()->startOfMonth()->addMonth(),
            ],
        ];

        foreach ($notifications as $notification) {
            Notification::create($notification);
        }
    }
}
