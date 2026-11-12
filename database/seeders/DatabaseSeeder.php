<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\AdminPanel\Database\Seeders\AdminPanelDatabaseSeeder;
use Modules\AdminPanel\Database\Seeders\BankSeeder;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CostCenter;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;
use Modules\Core\Models\LreConcept;
use Modules\Core\Models\Tenant;
use Modules\Payroll\Models\PayrollItem;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(TenantContext::class)->bypass();

        // 1. Roles base del sistema
        foreach (['super-admin', 'admin', 'contador', 'recursos-humanos', 'employee', 'multi-company'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Catálogos de AdminPanel y Core (AFP, Isapre, CCAF, Mutuales, Bancos, LRE DT, Indicadores Mensuales)
        $this->call([
            AdminPanelDatabaseSeeder::class,
            BankSeeder::class,
            CoreDatabaseSeeder::class,
            CodigoSiiSeeder::class,
        ]);

        // 3. Tenant y Empresa Demo inicial
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'grupo-remsis-demo'],
            [
                'name' => 'Grupo Empresarial Remsis Demo',
                'rut' => '76123456-7',
                'status' => Tenant::STATUS_ACTIVE,
            ]
        );

        $company = Company::firstOrCreate(
            ['rut' => '76543210-9'],
            [
                'tenant_id' => $tenant->id,
                'name' => ' Constructora & Servicios Andina SpA',
                'razon_social' => 'Constructora & Servicios Andina SpA',
                'nombre_fantasia' => 'Andina SpA',
                'giro' => 'Ingeniería, Construcción y Servicios Empresariales',
                'email' => 'contacto@andina.cl',
                'phone' => '+56223456789',
                'weekly_hours' => 42,
                'gratification_system' => Company::GRATIFICATION_SYSTEM_ART_50,
                'allows_overtime' => true,
                'payment_frequency' => 'mensual',
            ]
        );

        CostCenter::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'ADM-01'],
            [
                'name' => 'Administración Central',
                'status' => 'active',
            ]
        );

        CostCenter::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'OPE-01'],
            [
                'name' => 'Operaciones y Proyectos',
                'status' => 'active',
            ]
        );

        // 4. Conceptos iniciales de nómina vinculados a LRE DT para la empresa demo
        $lreByCode = LreConcept::query()->pluck('id', 'code');
        $defaultItems = [
            ['code' => 'BON_PROD', 'name' => 'Bono de Producción', 'type' => PayrollItem::TYPE_HABER_IMPONIBLE, 'lre_code' => '1106'],
            ['code' => 'COLACION', 'name' => 'Asignación de Colación', 'type' => PayrollItem::TYPE_HABER_NO_IMPONIBLE, 'lre_code' => '2101'],
            ['code' => 'MOVILIZACION', 'name' => 'Asignación de Movilización', 'type' => PayrollItem::TYPE_HABER_NO_IMPONIBLE, 'lre_code' => '2102'],
            ['code' => 'ANTICIPO', 'name' => 'Anticipo de Sueldo', 'type' => PayrollItem::TYPE_DESCUENTO_VARIOS, 'lre_code' => '3201'],
        ];

        foreach ($defaultItems as $tpl) {
            PayrollItem::updateOrCreate(
                ['company_id' => $company->id, 'code' => $tpl['code']],
                [
                    'tenant_id' => $tenant->id,
                    'lre_concept_id' => $lreByCode[$tpl['lre_code']] ?? null,
                    'name' => $tpl['name'],
                    'type' => $tpl['type'],
                    'calculation_type' => PayrollItem::CALC_FIJO,
                    'assignment_type' => PayrollItem::ASSIGN_DISTINTO_POR_PERSONA,
                    'currency' => 'CLP',
                ]
            );
        }

        // 5. Usuarios Super-Admin / Admin principales
        $adminEmails = ['admin@remsys.com', 'admin@remsis.local', 'test@example.com', 'cristiancartesa@gmail.com'];
        foreach ($adminEmails as $email) {
            $adminUser = User::updateOrCreate(
                ['email' => $email],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Administrador Remsis',
                    'password' => Hash::make('password'),
                    'status' => true,
                ]
            );
            $adminUser->company_id = $company->id;
            $adminUser->save();
            $adminUser->syncRoles(['super-admin', 'admin']);
            $adminUser->companies()->syncWithoutDetaching([$company->id]);
        }

        // 6. Colaboradores y Contratos Demo
        $this->call([
            CompanyUserSeeder::class,
        ]);

        app(TenantContext::class)->bypass(false);
    }
}
