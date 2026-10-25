<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoReporteSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        DB::table('estados_reporte')->upsert(
            [
                [
                    'id_estado_reporte' => 1,
                    'estado_reporte' => 'Pendiente',
                ],
                [
                    'id_estado_reporte' => 2,
                    'estado_reporte' => 'En revisión',
                ],
                [
                    'id_estado_reporte' => 3,
                    'estado_reporte' => 'Concluido',
                ],
            ],
            ['id_estado_reporte'],
            ['estado_reporte']
        );

        /*
         * Sincroniza la secuencia autoincremental de PostgreSQL
         * después de insertar identificadores explícitos.
         */
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('estados_reporte', 'id_estado_reporte'),
                COALESCE((SELECT MAX(id_estado_reporte) FROM estados_reporte), 1),
                true
            );
        ");
    }
}