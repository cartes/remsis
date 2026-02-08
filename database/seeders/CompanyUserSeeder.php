<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Models\User;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurar que los roles necesarios existen
        $roles = ['admin', 'contador', 'recursos-humanos', 'employee'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $companies = Company::all();

        if ($companies->isEmpty()) {
            return;
        }

        foreach ($companies as $company) {
            // 1. Usuarios Administrativos por Empresa
            $adminRoles = ['admin', 'contador', 'recursos-humanos'];
            foreach ($adminRoles as $role) {
                $roleLabel = ucfirst(str_replace('-', ' ', $role));
                $email = strtolower($role) . "_" . $company->id . "@remsis.cl";
                
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => "{$roleLabel} - {$company->name}",
                        'password' => Hash::make('password'),
                        'status' => true,
                    ]
                );
                
                // Asignar directamente el company_id al usuario (vía migración previa)
                $user->company_id = $company->id;
                $user->save();
                
                $user->syncRoles([$role]);
            }

            // 2. Empleados (5 a 10 por empresa)
            $numEmployees = rand(5, 10);
            for ($i = 1; $i <= $numEmployees; $i++) {
                $email = "empleado{$i}_{$company->id}@remsis.cl";
                
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => "Empleado {$i} - {$company->name}",
                        'password' => Hash::make('password'),
                        'status' => true,
                    ]
                );
                
                $user->company_id = $company->id;
                $user->save();
                $user->syncRoles(['employee']);

                // Crear registro en tabla employees
                Employee::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'company_id' => $company->id,
                        'first_name' => "Empleado",
                        'last_name' => "{$i} {$company->name}",
                        'rut' => rand(10000000, 25000000) . "-" . rand(0, 9),
                        'email' => $email,
                        'phone' => '+569' . rand(11111111, 88888888),
                        'position' => 'Ficha técnica ' . $i,
                        'salary' => rand(500000, 1800000),
                        'salary_type' => 'mensual',
                        'contract_type' => 'indefinido',
                        'status' => 'active', // Corregido de 'activo' a 'active' según enumeración
                        'hire_date' => now()->subDays(rand(30, 365)),
                    ]
                );
            }
        }
    }
}
