<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoNotificacionSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        DB::table('tipos_notificacion')->upsert(
            [
                [
                    'id_tipo_notificacion' => 1,
                    'tipo_notificacion' => 'SOLICITUD',
                ],
                [
                    'id_tipo_notificacion' => 2,
                    'tipo_notificacion' => 'CONTRATACION',
                ],
                [
                    'id_tipo_notificacion' => 3,
                    'tipo_notificacion' => 'PUBLICACION',
                ],
                [
                    'id_tipo_notificacion' => 4,
                    'tipo_notificacion' => 'SISTEMA',
                ],
                [
                    'id_tipo_notificacion' => 5,
                    'tipo_notificacion' => 'REPORTE',
                ],
            ],
            ['id_tipo_notificacion'],
            ['tipo_notificacion']
        );

        /*
         * Sincroniza la secuencia autoincremental de PostgreSQL
         * después de insertar identificadores explícitos.
         */
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('tipos_notificacion', 'id_tipo_notificacion'),
                COALESCE((SELECT MAX(id_tipo_notificacion) FROM tipos_notificacion), 1),
                true
            );
        ");
    }
}