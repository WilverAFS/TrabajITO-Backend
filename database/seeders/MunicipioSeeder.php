<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MunicipioSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        DB::table('municipios')->upsert(
            [
                [
                    'id_municipio' => 1,
                    'id_estado' => 1,
                    'municipio' => 'Oaxaca de Juárez',
                    'activo' => true,
                ],
                [
                    'id_municipio' => 2,
                    'id_estado' => 1,
                    'municipio' => 'San Jacinto Amilpas',
                    'activo' => true,
                ],
                [
                    'id_municipio' => 3,
                    'id_estado' => 1,
                    'municipio' => 'Santa Lucía del Camino',
                    'activo' => true,
                ],
                [
                    'id_municipio' => 4,
                    'id_estado' => 1,
                    'municipio' => 'Santa Cruz Xoxocotlán',
                    'activo' => true,
                ],
                [
                    'id_municipio' => 5,
                    'id_estado' => 1,
                    'municipio' => 'Santa María Atzompa',
                    'activo' => true,
                ],
                [
                    'id_municipio' => 6,
                    'id_estado' => 1,
                    'municipio' => 'San Antonio de la Cal',
                    'activo' => true,
                ],
                [
                    'id_municipio' => 7,
                    'id_estado' => 1,
                    'municipio' => 'Santa Cruz Amilpas',
                    'activo' => true,
                ],
                [
                    'id_municipio' => 8,
                    'id_estado' => 1,
                    'municipio' => 'San Sebastián Tutla',
                    'activo' => true,
                ],
            ],
            ['id_municipio'],
            ['id_estado', 'municipio', 'activo']
        );

        /*
         * Sincroniza la secuencia autoincremental de PostgreSQL
         * después de insertar identificadores explícitos.
         */
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('municipios', 'id_municipio'),
                COALESCE((SELECT MAX(id_municipio) FROM municipios), 1),
                true
            );
        ");
    }
}