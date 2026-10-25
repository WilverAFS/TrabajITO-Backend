<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoPublicacionSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        DB::table('tipos_publicacion')->upsert(
            [
                [
                    'id_tipo_publicacion' => 1,
                    'tipo_publicacion' => 'Diagnóstico',
                ],
                [
                    'id_tipo_publicacion' => 2,
                    'tipo_publicacion' => 'Instalación',
                ],
                [
                    'id_tipo_publicacion' => 3,
                    'tipo_publicacion' => 'Reparación',
                ],
                [
                    'id_tipo_publicacion' => 4,
                    'tipo_publicacion' => 'Mantenimiento',
                ],
                [
                    'id_tipo_publicacion' => 5,
                    'tipo_publicacion' => 'Construcción',
                ],
            ],
            ['id_tipo_publicacion'],
            ['tipo_publicacion']
        );

        /*
         * Sincroniza la secuencia autoincremental de PostgreSQL
         * después de insertar identificadores explícitos.
         */
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('tipos_publicacion', 'id_tipo_publicacion'),
                COALESCE((SELECT MAX(id_tipo_publicacion) FROM tipos_publicacion), 1),
                true
            );
        ");
    }
}