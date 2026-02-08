<?php

namespace Modules\Payroll\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Payroll\Models\PayrollPeriod;
use Carbon\Carbon;

class PayrollPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periods = [
            [
                'name' => 'Enero 2024',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'payment_date' => '2024-02-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Febrero 2024',
                'start_date' => '2024-02-01',
                'end_date' => '2024-02-29',
                'payment_date' => '2024-03-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Marzo 2024',
                'start_date' => '2024-03-01',
                'end_date' => '2024-03-31',
                'payment_date' => '2024-04-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Abril 2024',
                'start_date' => '2024-04-01',
                'end_date' => '2024-04-30',
                'payment_date' => '2024-05-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Mayo 2024',
                'start_date' => '2024-05-01',
                'end_date' => '2024-05-31',
                'payment_date' => '2024-06-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Junio 2024',
                'start_date' => '2024-06-01',
                'end_date' => '2024-06-30',
                'payment_date' => '2024-07-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Julio 2024',
                'start_date' => '2024-07-01',
                'end_date' => '2024-07-31',
                'payment_date' => '2024-08-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Agosto 2024',
                'start_date' => '2024-08-01',
                'end_date' => '2024-08-31',
                'payment_date' => '2024-09-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Septiembre 2024',
                'start_date' => '2024-09-01',
                'end_date' => '2024-09-30',
                'payment_date' => '2024-10-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Octubre 2024',
                'start_date' => '2024-10-01',
                'end_date' => '2024-10-31',
                'payment_date' => '2024-11-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Noviembre 2024',
                'start_date' => '2024-11-01',
                'end_date' => '2024-11-30',
                'payment_date' => '2024-12-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Diciembre 2024',
                'start_date' => '2024-12-01',
                'end_date' => '2024-12-31',
                'payment_date' => '2025-01-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Enero 2025',
                'start_date' => '2025-01-01',
                'end_date' => '2025-01-31',
                'payment_date' => '2025-02-05',
                'status' => 'closed',
            ],
            [
                'name' => 'Febrero 2025',
                'start_date' => '2025-02-01',
                'end_date' => '2025-02-28',
                'payment_date' => '2025-03-05',
                'status' => 'active',
            ],
        ];

        foreach ($periods as $period) {
            PayrollPeriod::create($period);
        }
    }
}
