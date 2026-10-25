<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSolicitudSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        DB::table('estados_solicitud')->upsert(
            [
                [
                    'id_estado_solicitud' => 1,
                    'estado_solicitud' => 'Pendiente',
                ],
                [
                    'id_estado_solicitud' => 2,
                    'estado_solicitud' => 'En revisión',
                ],
                [
                    'id_estado_solicitud' => 3,
                    'estado_solicitud' => 'Aceptada',
                ],
                [
                    'id_estado_solicitud' => 4,
                    'estado_solicitud' => 'Rechazada',
                ],
            ],
            ['id_estado_solicitud'],
            ['estado_solicitud']
        );

        /*
         * Sincroniza la secuencia autoincremental de PostgreSQL
         * después de insertar identificadores explícitos.
         */
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('estados_solicitud', 'id_estado_solicitud'),
                COALESCE((SELECT MAX(id_estado_solicitud) FROM estados_solicitud), 1),
                true
            );
        ");
    }
}