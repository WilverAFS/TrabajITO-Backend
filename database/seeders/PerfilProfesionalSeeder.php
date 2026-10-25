<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerfilProfesionalSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        DB::transaction(function () {
            /*
             * Perfiles profesionales.
             */
            DB::table('perfiles_profesionales')->upsert(
                [
                    [
                        'id_trabajador' => 1,
                        'id_usuario' => 3,
                        'id_municipio' => 1,
                        'descripcion' => 'Electricista con experiencia en instalaciones residenciales, diagnóstico de fallas y mantenimiento preventivo.',
                        'fecha_registro' => now(),
                        'activo' => true,
                    ],
                    [
                        'id_trabajador' => 2,
                        'id_usuario' => 4,
                        'id_municipio' => 3,
                        'descripcion' => 'Especialista en plomería, reparación de fugas, instalaciones sanitarias y mantenimiento hidráulico.',
                        'fecha_registro' => now(),
                        'activo' => true,
                    ],
                ],
                ['id_trabajador'],
                ['id_usuario', 'id_municipio', 'descripcion', 'fecha_registro', 'activo']
            );

            /*
             * Asociaciones con oficios.
             *
             * Perfil 1: Electricidad y refrigeración.
             * Perfil 2: Plomería.
             */
            DB::table('perfil_oficio')->upsert(
                [
                    [
                        'id_trabajador' => 1,
                        'id_oficio' => 2,
                    ],
                    [
                        'id_trabajador' => 1,
                        'id_oficio' => 8,
                    ],
                    [
                        'id_trabajador' => 2,
                        'id_oficio' => 1,
                    ],
                ],
                ['id_trabajador', 'id_oficio'],
                []
            );

            /*
             * Disponibilidades.
             */
            DB::table('disponibilidades')->upsert(
                [
                    [
                        'id_disponibilidad' => 1,
                        'id_trabajador' => 1,
                        'dia_semana' => 1,
                        'hora_inicio' => '09:00:00',
                        'hora_fin' => '14:00:00',
                        'activo' => true,
                    ],
                    [
                        'id_disponibilidad' => 2,
                        'id_trabajador' => 1,
                        'dia_semana' => 3,
                        'hora_inicio' => '09:00:00',
                        'hora_fin' => '14:00:00',
                        'activo' => true,
                    ],
                    [
                        'id_disponibilidad' => 3,
                        'id_trabajador' => 1,
                        'dia_semana' => 5,
                        'hora_inicio' => '09:00:00',
                        'hora_fin' => '17:00:00',
                        'activo' => true,
                    ],
                    [
                        'id_disponibilidad' => 4,
                        'id_trabajador' => 2,
                        'dia_semana' => 2,
                        'hora_inicio' => '08:00:00',
                        'hora_fin' => '13:00:00',
                        'activo' => true,
                    ],
                    [
                        'id_disponibilidad' => 5,
                        'id_trabajador' => 2,
                        'dia_semana' => 4,
                        'hora_inicio' => '08:00:00',
                        'hora_fin' => '13:00:00',
                        'activo' => true,
                    ],
                    [
                        'id_disponibilidad' => 6,
                        'id_trabajador' => 2,
                        'dia_semana' => 6,
                        'hora_inicio' => '09:00:00',
                        'hora_fin' => '15:00:00',
                        'activo' => true,
                    ],
                ],
                ['id_disponibilidad'],
                ['id_trabajador', 'dia_semana', 'hora_inicio', 'hora_fin', 'activo']
            );

            /*
             * Certificados.
             */
            DB::table('certificados')->updateOrInsert(
                ['id_certificado' => 1],
                [
                    'id_trabajador' => 1,
                    'nombre' => 'Instalaciones eléctricas residenciales',
                    'institucion_emisora' => 'Institución de capacitación de prueba',
                    'fecha_emision' => '2025-05-15',
                    'fecha_vencimiento' => null,
                    'ruta_documento' => 'certificados/prueba/certificado-electricidad.pdf',
                    'verificado' => true,
                ]
            );

            /*
             * Solicitudes de habilitación.
             *
             * Estado 3 = Aceptada.
             * Usuario 1 = Administrador.
             */
            DB::table('solicitudes_habilitacion')->upsert(
                [
                    [
                        'id_solicitud' => 1,
                        'id_trabajador' => 1,
                        'id_admin_revisor' => 1,
                        'id_estado_solicitud' => 3,
                        'comentario_revision' => 'Información profesional validada correctamente.',
                        'fecha_solicitud' => now()->subDays(20),
                        'fecha_revision' => now()->subDays(18),
                    ],
                    [
                        'id_solicitud' => 2,
                        'id_trabajador' => 2,
                        'id_admin_revisor' => 1,
                        'id_estado_solicitud' => 3,
                        'comentario_revision' => 'Perfil aprobado para ofrecer servicios.',
                        'fecha_solicitud' => now()->subDays(15),
                        'fecha_revision' => now()->subDays(14),
                    ],
                ],
                ['id_solicitud'],
                ['id_trabajador', 'id_admin_revisor', 'id_estado_solicitud', 'comentario_revision', 'fecha_solicitud', 'fecha_revision']
            );

            /*
             * Sincronización de secuencias de PostgreSQL.
             */
            $this->sincronizarSecuencia('perfiles_profesionales', 'id_trabajador');
            $this->sincronizarSecuencia('disponibilidades', 'id_disponibilidad');
            $this->sincronizarSecuencia('certificados', 'id_certificado');
            $this->sincronizarSecuencia('solicitudes_habilitacion', 'id_solicitud');
        });
    }

    /**
     * Sincroniza una secuencia autoincremental de PostgreSQL.
     */
    private function sincronizarSecuencia(
        string $tabla,
        string $columna
    ): void {
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('{$tabla}', '{$columna}'),
                COALESCE((SELECT MAX({$columna}) FROM {$tabla}), 1),
                true
            );
        ");
    }
}