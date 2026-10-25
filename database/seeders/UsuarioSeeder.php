<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        DB::table('users')->upsert(
            [
                [
                    'id_usuario' => 1,
                    'nombre' => 'Administrador',
                    'apellido' => 'TrabajITO',
                    'correo' => 'admin@trabajito.test',
                    'contrasena_hash' => Hash::make('Admin12345!'),
                    'telefono' => '9510000001',
                    'es_administrador' => true,
                    'activo' => true,
                    'fecha_registro' => now(),
                ],
                [
                    'id_usuario' => 2,
                    'nombre' => 'Mariana',
                    'apellido' => 'López García',
                    'correo' => 'cliente@trabajito.test',
                    'contrasena_hash' => Hash::make('Cliente12345!'),
                    'telefono' => '9510000002',
                    'es_administrador' => false,
                    'activo' => true,
                    'fecha_registro' => now(),
                ],
                [
                    'id_usuario' => 3,
                    'nombre' => 'Carlos',
                    'apellido' => 'Martínez Ruiz',
                    'correo' => 'trabajador1@trabajito.test',
                    'contrasena_hash' => Hash::make('Trabajador12345!'),
                    'telefono' => '9510000003',
                    'es_administrador' => false,
                    'activo' => true,
                    'fecha_registro' => now(),
                ],
                [
                    'id_usuario' => 4,
                    'nombre' => 'Elena',
                    'apellido' => 'Sánchez Pérez',
                    'correo' => 'trabajador2@trabajito.test',
                    'contrasena_hash' => Hash::make('Trabajador12345!'),
                    'telefono' => '9510000004',
                    'es_administrador' => false,
                    'activo' => true,
                    'fecha_registro' => now(),
                ],
            ],
            ['id_usuario'],
            ['nombre', 'apellido', 'correo', 'contrasena_hash', 'telefono', 'es_administrador', 'activo', 'fecha_registro']
        );

        /*
         * Ajusta la secuencia porque insertamos identificadores
         * explícitos.
         */
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('users', 'id_usuario'),
                COALESCE((SELECT MAX(id_usuario) FROM users), 1),
                true
            );
        ");
    }
}