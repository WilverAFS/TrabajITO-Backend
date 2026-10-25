<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('estados')->upsert(
            [
                [
                    'id_estado'=> 1,
                    'estado' => 'Oaxaca',
                    'activo' => true,
                ],
            ],
            ['id_estado'],
            ['estado', 'activo']
        );

        //Sincroniza la secuencia autoincremental de PostgreSQL
        //despues de insertar identificadores explicitos
        DB::statement(
            "SELECT setval(
                pg_get_serial_sequence('estados', 'id_estado'),
                COALESCE((SELECT MAX(id_estado) FROM estados), 1),
                true
            );"
        );
    }
}
