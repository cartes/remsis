<?php

namespace Database\Seeders;

use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CostCenter;
use Modules\Employees\Models\AttendanceRecord;
use Modules\Employees\Models\Employee;
use Modules\Payroll\Models\PayrollPeriod;

class AndinaDemoAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::withoutGlobalScopes()->where('rut', '76543210-9')->firstOrFail();
        $centers = CostCenter::withoutGlobalScopes()->where('company_id', $company->id)->pluck('id', 'code');
        $staff = [
            ['Camila', 'Soto Vera', 'Coordinadora de personal', 'ADM-01'],
            ['Diego', 'Leiva Ruiz', 'Supervisor de obra', 'OPE-01'],
            ['Paula', 'Rojas Silva', 'Analista contable', 'ADM-01'],
            ['Matías', 'Vega Lagos', 'Maestro carpintero', 'OPE-01'],
            ['Daniela', 'Mella Díaz', 'Prevencionista de riesgos', 'OPE-01'],
            ['Felipe', 'Díaz Mora', 'Operador de maquinaria', 'OPE-01'],
            ['Valentina', 'Araya Toro', 'Encargada de bodega', 'OPE-01'],
            ['Sebastián', 'Ríos Paz', 'Técnico eléctrico', 'OPE-01'],
            ['Natalia', 'Fuentes Peña', 'Asistente de compras', 'ADM-01'],
            ['Javier', 'Núñez Vidal', 'Coordinador de proyectos', 'OPE-01'],
        ];

        $context = app(TenantContext::class);
        $previousCompany = $context->companyId();
        $context->setCompanyId($company->id);
        try {
            DB::transaction(function () use ($company, $centers, $staff) {
                $closed = PayrollPeriod::withoutGlobalScopes()->where('company_id', $company->id)
                    ->where('year', 2026)->where('month', 11)->whereIn('status', ['closed', 'paid'])->exists();

                foreach ($staff as $index => [$first, $last, $position, $center]) {
                    $number = $index + 1;
                    $email = "empleado{$number}_{$company->id}@remsis.cl";
                    $employee = Employee::withoutGlobalScopes()->where('company_id', $company->id)
                        ->where('email', $email)->whereHas('user', fn ($query) => $query->where('email', $email))->first();
                    if (! $employee) {
                        continue;
                    }

                    if ($employee->first_name === 'Empleado' && $employee->position === "Ficha técnica {$number}") {
                        $employee->update(['tenant_id' => $company->tenant_id, 'first_name' => $first, 'last_name' => $last,
                            'position' => $position, 'cost_center_id' => $centers[$center]]);
                        $employee->user->update(['name' => "$first $last"]);
                        $employee->syncActiveContract();
                    }

                    if ($closed || $employee->first_name !== $first || $employee->last_name !== $last) {
                        continue;
                    }

                    for ($day = 2; $day <= 13; $day++) {
                        $date = CarbonImmutable::create(2026, 11, $day, 0, 0, 0, 'America/Santiago');
                        if ($date->isWeekend() || $date->isFuture() || $employee->hire_date?->toDateString() > $date->toDateString()) {
                            continue;
                        }

                        $records = AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employee->id);
                        if ($records->whereDate('date', $date->toDateString())->exists()) {
                            continue;
                        }

                        $end = $date->isFriday() ? '15:30' : '17:30';
                        $entry = ['status' => 'asistio', 'clock_in' => '08:00', 'clock_out' => $end];
                        if ($number === 2 && $day === 9) {
                            $entry = ['status' => 'atraso', 'clock_in' => '08:12', 'clock_out' => $end, 'delay_minutes' => 12];
                        } elseif ($number === 3 && in_array($day, [10, 11])) {
                            $entry = ['status' => 'licencia', 'is_justified' => true, 'justification_type' => 'licencia_medica'];
                        } elseif ($number === 4 && $day === 12) {
                            $entry = ['status' => 'ausente', 'is_justified' => true, 'justification_type' => 'permiso_con_goce'];
                        } elseif ($number === 6 && $day === 5) {
                            $entry['clock_out'] = '18:30';
                            $entry['overtime_minutes'] = 60;
                        }
                        AttendanceRecord::create($entry + ['company_id' => $company->id, 'tenant_id' => $company->tenant_id,
                            'employee_id' => $employee->id, 'date' => $date->toDateString(), 'scheduled_shift' => "08:00 - $end"]);
                    }
                }
            });
        } finally {
            $context->setCompanyId($previousCompany);
        }
    }
}
