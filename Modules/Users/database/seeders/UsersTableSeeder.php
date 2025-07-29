<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles si no existen
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $contadorRole = Role::firstOrCreate([
            'name' => 'contador',
            'guard_name' => 'web',
        ]);

        $employeeRole = Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        // Crear usuarios con metadata
        $admin = User::create([
            'name' => 'Admin General',
            'email' => 'admin@remsis.local',
            'password' => Hash::make('password'),
            'type' => 'admin',
            'metadata' => [
                'telefono' => '987654321',
                'tema' => 'oscuro',
            ],
        ]);
        $admin->assignRole($adminRole);

        $contador = User::create([
            'name' => 'Claudia Contadora',
            'email' => 'claudia@empresa.cl',
            'password' => Hash::make('password'),
            'type' => 'contador',
            'metadata' => [
                'departamento' => 'Contabilidad',
                'telefono' => '912345678',
            ],
        ]);
        $contador->assignRole($contadorRole);

        $empleado = User::create([
            'name' => 'Carlos Empleado',
            'email' => 'carlos@empresa.cl',
            'password' => Hash::make('password'),
            'type' => 'employee',
            'metadata' => [
                'cargo' => 'Analista Junior',
                'centro_costo' => 'Sucursal Viña del Mar',
            ],
        ]);
        $empleado->assignRole($employeeRole);
    }
}
