<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OficioSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        DB::table('oficios')->upsert(
            [
                [
                    'id_oficio' => 1,
                    'nombre' => 'Plomería',
                    'descripcion' => 'Instalación, reparación y mantenimiento de sistemas hidráulicos y sanitarios.',
                    'activo' => true,
                ],
                [
                    'id_oficio' => 2,
                    'nombre' => 'Electricidad',
                    'descripcion' => 'Instalación, diagnóstico y reparación de sistemas eléctricos.',
                    'activo' => true,
                ],
                [
                    'id_oficio' => 3,
                    'nombre' => 'Carpintería',
                    'descripcion' => 'Fabricación, instalación y reparación de muebles y estructuras de madera.',
                    'activo' => true,
                ],
                [
                    'id_oficio' => 4,
                    'nombre' => 'Pintura',
                    'descripcion' => 'Aplicación y mantenimiento de pintura en interiores y exteriores.',
                    'activo' => true,
                ],
                [
                    'id_oficio' => 5,
                    'nombre' => 'Herrería',
                    'descripcion' => 'Fabricación y reparación de estructuras y elementos metálicos.',
                    'activo' => true,
                ],
                [
                    'id_oficio' => 6,
                    'nombre' => 'Albañilería',
                    'descripcion' => 'Construcción, reparación y mantenimiento de obras y estructuras.',
                    'activo' => true,
                ],
                [
                    'id_oficio' => 7,
                    'nombre' => 'Jardinería',
                    'descripcion' => 'Mantenimiento, diseño y cuidado de jardines y áreas verdes.',
                    'activo' => true,
                ],
                [
                    'id_oficio' => 8,
                    'nombre' => 'Refrigeración y aire acondicionado',
                    'descripcion' => 'Instalación, diagnóstico y mantenimiento de equipos de refrigeración y climatización.',
                    'activo' => true,
                ],
            ],
            ['id_oficio'],
            ['nombre', 'descripcion', 'activo']
        );

        /*
         * Sincroniza la secuencia autoincremental de PostgreSQL
         * después de insertar identificadores explícitos.
         */
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('oficios', 'id_oficio'),
                COALESCE((SELECT MAX(id_oficio) FROM oficios), 1),
                true
            );
        ");
    }
}