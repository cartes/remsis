<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AdminPanel\Models\CodigoSii;

class CodigoSiiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $codigos = [
            ['672020', 'AGENTES Y LIQUIDADORES DE SEGUROS', 13.5],
            ['722000', 'ASESORES Y CONSULTORES EN INFORMATICA (SOFTWARE)', 13.5],
            ['741110', 'SERVICIOS JURIDICOS', 13.5],
            ['741120', 'SERVICIO NOTARIAL', 13.5],
            ['741130', 'CONSERVADOR DE BIENES RAICES', 13.5],
            ['741140', 'RECEPTORES JUDICIALES', 13.5],
            ['741190', 'ARBITRAJES, SINDICOS, PERITOS Y OTROS', 13.5],
            ['741200', 'ACTIVIDADES DE CONTABILIDAD, TENEDURIA DE LIBROS Y AUDITORIA; ASESORAMIENTOS TRIBUTARIOS', 13.5],
            ['742110', 'SERVICIOS DE ARQUITECTURA Y TECNICO RELACIONADO', 13.5],
            ['742122', 'SERVICIOS PROFESIONALES EN GEOLOGIA Y PROSPECCION', 13.5],
            ['742132', 'SERVICIOS PROFESIONALES DE TOPOGRAFIA Y AGRIMENSURA', 13.5],
            ['742142', 'SERVICIOS DE INGENIERIA PRESTADOS POR PROFESIONALES N.C.P.', 13.5],
            ['742190', 'OTROS SERVICIOS DESARROLLADOS POR PROFESIONALES', 13.5],
            ['743002', 'SERVICIOS PERSONALES EN PUBLICIDAD', 13.5],
            ['749229', 'SERVICIOS PERSONALES RELACIONADOS CON SEGURIDAD', 13.5],
            ['749409', 'SERVICIOS PERSONALES DE FOTOGRAFIA', 13.5],
            ['749932', 'SERVICIOS PERSONALES DE TRADUCCION, INTERPRETACION Y LABORES DE OFICINA', 13.5],
            ['809049', 'SERVICIOS PERSONALES DE EDUCACION', 13.5],
            ['851211', 'SERVICIOS DE MEDICOS EN FORMA INDEPENDIENTE', 13.5],
            ['851221', 'SERVICIOS DE ODONTOLOGOS EN FORMA INDEPENDIENTE', 13.5],
            ['851920', 'OTROS PROFESIONALES DE LA SALUD', 13.5],
            ['852021', 'SERVICIOS DE MEDICOS VETERINARIOS EN FORMA INDEPENDIENTE', 13.5],
            ['852029', 'SERVICIOS DE OTROS PROFESIONALES INDEPENDIENTES EN EL AREA VETERINARIA', 13.5],
            ['921430', 'ACTIVIDADES ARTISTICAS: FUNCIONES DE ARTISTAS, ACTORES, MUSICOS, CONFERENCISTAS, OTROS', 13.5],
            ['921911', 'INSTRUCTORES DE DANZA', 13.5],
            ['922002', 'SERVICIOS PERIODISTICOS PRESTADO POR PROFESIONALES', 13.5],
            ['930990', 'OTRAS ACTIVIDADES DE SERVICIOS PERSONALES N.C.P.', 13.5],
        ];

        foreach ($codigos as [$codigo, $glosa, $utms_min]) {
            CodigoSii::updateOrCreate(
                ['codigo' => $codigo],
                [
                    'glosa' => $glosa,
                    'utms_min' => $utms_min,
                    'categoria' => '2da',
                    'activo' => true
                ]
            );
        }
    }
}
