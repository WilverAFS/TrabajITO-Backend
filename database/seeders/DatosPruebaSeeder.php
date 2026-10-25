<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatosPruebaSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        DB::transaction(function () {
            /*
             * Contratación finalizada.
             *
             * Cliente: usuario 2.
             * Trabajador: perfil 1.
             * Estado 5: Finalizada.
             */
            DB::table('contrataciones')->updateOrInsert(
                ['id_contratacion' => 1],
                [
                    'id_cliente' => 2,
                    'id_trabajador' => 1,
                    'id_estado_contratacion' => 5,
                    'descripcion_servicio' => 'Revisión y reparación de una instalación eléctrica residencial con contactos sin funcionamiento.',
                    'fecha_solicitud' => now()->subDays(12),
                    'fecha_respuesta' => now()->subDays(11),
                    'fecha_finalizacion' => now()->subDays(8),
                ]
            );

            /*
             * Publicación asociada.
             *
             * Tipo 3: Reparación.
             */
            DB::table('publicaciones')->updateOrInsert(
                ['id_publicacion' => 1],
                [
                    'id_contratacion' => 1,
                    'id_tipo_publicacion' => 3,
                    'titulo' => 'Reparación de instalación eléctrica residencial',
                    'descripcion' => 'Diagnóstico y sustitución de conexiones dañadas en una instalación eléctrica residencial.',
                    'visible' => true,
                    'fecha_publicacion' => now()->subDays(7),
                ]
            );

            /*
             * Evidencias.
             */
            DB::table('evidencias')->upsert(
                [
                    [
                        'id_evidencia' => 1,
                        'id_publicacion' => 1,
                        'tipo_archivo' => 'IMAGEN',
                        'ruta_archivo' => 'publicaciones/prueba/reparacion-electrica-1.jpg',
                        'fecha_carga' => now()->subDays(7),
                    ],
                    [
                        'id_evidencia' => 2,
                        'id_publicacion' => 1,
                        'tipo_archivo' => 'IMAGEN',
                        'ruta_archivo' => 'publicaciones/prueba/reparacion-electrica-2.jpg',
                        'fecha_carga' => now()->subDays(7),
                    ],
                ],
                ['id_evidencia'],
                ['id_publicacion', 'tipo_archivo', 'ruta_archivo', 'fecha_carga']
            );

            /*
             * Valoración.
             */
            DB::table('valoraciones')->updateOrInsert(
                ['id_valoracion' => 1],
                [
                    'id_publicacion' => 1,
                    'id_cliente' => 2,
                    'calificacion' => 5,
                    'comentario' => 'El trabajo fue realizado correctamente y la instalación quedó funcionando.',
                    'fecha_valoracion' => now()->subDays(6),
                ]
            );

            /*
             * Reporte concluido contra la publicación.
             *
             * Estado 3: Concluido.
             */
            DB::table('reportes')->updateOrInsert(
                ['id_reporte' => 1],
                [
                    'id_denunciante' => 4,
                    'tipo_objetivo' => 'PUBLICACION',
                    'id_objetivo' => 1,
                    'id_admin_moderador' => 1,
                    'motivo' => 'Revisión de contenido',
                    'descripcion' => 'Reporte de prueba para validar el flujo administrativo de moderación.',
                    'id_estado_reporte' => 3,
                    'resolucion' => 'El contenido fue revisado y no se encontraron incumplimientos.',
                    'fecha_creacion' => now()->subDays(5),
                    'fecha_resolucion' => now()->subDays(4),
                ]
            );

            /*
             * Notificaciones.
             */
            DB::table('notificaciones')->upsert(
                [
                    [
                        'id_notificacion' => 1,
                        'id_usuario' => 3,
                        'id_tipo_notificacion' => 2,
                        'titulo' => 'Nueva solicitud de contratación',
                        'mensaje' => 'Has recibido una solicitud para un servicio eléctrico.',
                        'leida' => true,
                        'fecha_creacion' => now()->subDays(12),
                    ],
                    [
                        'id_notificacion' => 2,
                        'id_usuario' => 2,
                        'id_tipo_notificacion' => 2,
                        'titulo' => 'Solicitud aceptada',
                        'mensaje' => 'El trabajador aceptó tu solicitud de contratación.',
                        'leida' => true,
                        'fecha_creacion' => now()->subDays(11),
                    ],
                    [
                        'id_notificacion' => 3,
                        'id_usuario' => 2,
                        'id_tipo_notificacion' => 3,
                        'titulo' => 'Trabajo publicado',
                        'mensaje' => 'El trabajador publicó la evidencia del trabajo realizado.',
                        'leida' => false,
                        'fecha_creacion' => now()->subDays(7),
                    ],
                    [
                        'id_notificacion' => 4,
                        'id_usuario' => 4,
                        'id_tipo_notificacion' => 5,
                        'titulo' => 'Reporte resuelto',
                        'mensaje' => 'El reporte de prueba fue revisado por un administrador.',
                        'leida' => false,
                        'fecha_creacion' => now()->subDays(4),
                    ],
                ],
                ['id_notificacion'],
                ['id_usuario', 'id_tipo_notificacion', 'titulo', 'mensaje', 'leida', 'fecha_creacion']
            );

            /*
             * Sincronización de secuencias de PostgreSQL.
             */
            $this->sincronizarSecuencia('contrataciones', 'id_contratacion');
            $this->sincronizarSecuencia('publicaciones', 'id_publicacion');
            $this->sincronizarSecuencia('evidencias', 'id_evidencia');
            $this->sincronizarSecuencia('valoraciones', 'id_valoracion');
            $this->sincronizarSecuencia('reportes', 'id_reporte');
            $this->sincronizarSecuencia('notificaciones', 'id_notificacion');
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