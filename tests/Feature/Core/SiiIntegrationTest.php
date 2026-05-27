<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\Models\EconomicActivity;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol de admin y asignar a usuario de prueba
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($adminRole);

        // Crear algunas actividades económicas en la base de datos
        EconomicActivity::updateOrCreate(
            ['code' => '620100'],
            ['name' => 'ACTIVIDADES DE PROGRAMACIÓN INFORMÁTICA', 'category' => '1']
        );
        EconomicActivity::updateOrCreate(
            ['code' => '692000'],
            ['name' => 'ACTIVIDADES DE CONTABILIDAD, TENEDURÍA DE LIBROS Y AUDITORÍA', 'category' => '1']
        );
    }

    public function test_sii_service_returns_mock_data_for_specific_ruts(): void
    {
        config(['services.sii.driver' => 'mock']);

        $siiService = resolve(\Modules\Core\Services\SiiService::class);

        // Caso RUT Remsis
        $data = $siiService->fetchCompanyDataByRut('76.123.456-7');
        $this->assertSame('SERVICIOS TECNOLÓGICOS REMSIS SPA', $data['razon_social']);
        $this->assertSame('SERVICIOS INFORMÁTICOS Y DESARROLLO DE SOFTWARE', $data['giro']);
        $this->assertNotEmpty($data['economic_activities']);
        $this->assertSame('620100', $data['primary_activity']['code']);
        $this->assertNotNull($data['primary_activity']['id']);

        // Caso RUT desconocido (debe generar mock por defecto y asociar una actividad si existe)
        $dataRandom = $siiService->fetchCompanyDataByRut('76.888.888-8');
        $this->assertStringContainsString('EMPRESA TENANT DEMO', $dataRandom['razon_social']);
        $this->assertNotEmpty($dataRandom['economic_activities']);
    }

    public function test_sii_service_normalizes_data_from_external_api(): void
    {
        config(['services.sii.driver' => 'api']);

        Http::fake([
            'https://api.sre.cl/api/sii/rut/76123456-7' => Http::response([
                'razon_social' => 'Servicios de Prueba SpA',
                'giro' => 'SERVICIOS GENERALES',
                'actividades' => [
                    ['codigo' => '620100', 'nombre' => 'Actividades de programacion']
                ]
            ], 200)
        ]);

        $siiService = resolve(\Modules\Core\Services\SiiService::class);
        $data = $siiService->fetchCompanyDataByRut('76.123.456-7');

        $this->assertSame('SERVICIOS DE PRUEBA SPA', $data['razon_social']);
        $this->assertSame('SERVICIOS GENERALES', $data['giro']);
        $this->assertSame('620100', $data['primary_activity']['code']);
        $this->assertNotNull($data['primary_activity']['id']); // Debe resolver al ID en base de datos local
    }

    public function test_endpoint_returns_unauthorized_if_not_logged_in(): void
    {
        $response = $this->getJson(route('sii.consultar-rut', '76123456-7'));
        $response->assertUnauthorized();
    }

    public function test_endpoint_returns_json_response_when_authenticated(): void
    {
        config(['services.sii.driver' => 'mock']);

        $response = $this->actingAs($this->user)
            ->getJson(route('sii.consultar-rut', '76.123.456-7'));

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'razon_social',
                    'giro',
                    'economic_activities',
                    'primary_activity' => [
                        'code',
                        'name',
                        'id'
                    ]
                ]
            ]);

        $this->assertSame('success', $response->json('status'));
        $this->assertSame('SERVICIOS TECNOLÓGICOS REMSIS SPA', $response->json('data.razon_social'));
    }

    public function test_endpoint_returns_error_for_invalid_ruts(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('sii.consultar-rut', '123'));

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'RUT inválido o muy corto.'
            ]);
    }
}
