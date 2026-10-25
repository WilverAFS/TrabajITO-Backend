<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoContratacionSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        DB::table('estados_contratacion')->upsert(
            [
                [
                    'id_estado_contratacion' => 1,
                    'estado_contratacion' => 'Pendiente',
                ],
                [
                    'id_estado_contratacion' => 2,
                    'estado_contratacion' => 'Aceptada',
                ],
                [
                    'id_estado_contratacion' => 3,
                    'estado_contratacion' => 'Rechazada',
                ],
                [
                    'id_estado_contratacion' => 4,
                    'estado_contratacion' => 'En proceso',
                ],
                [
                    'id_estado_contratacion' => 5,
                    'estado_contratacion' => 'Finalizada',
                ],
                [
                    'id_estado_contratacion' => 6,
                    'estado_contratacion' => 'Cancelada',
                ],
            ],
            ['id_estado_contratacion'],
            ['estado_contratacion']
        );

        /*
         * Sincroniza la secuencia autoincremental de PostgreSQL
         * después de insertar identificadores explícitos.
         */
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('estados_contratacion', 'id_estado_contratacion'),
                COALESCE((SELECT MAX(id_estado_contratacion) FROM estados_contratacion), 1),
                true
            );
        ");
    }
}