<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\TipoNotificacion;
use App\Models\User;

class NotificacionService
{
    /**
     * Crea una notificación a partir del nombre del catálogo existente.
     * Si el catálogo todavía no fue sembrado, no interrumpe la operación de negocio.
     */
    public function crear(
        int $idUsuario,
        string $tipo,
        string $titulo,
        string $mensaje
    ): ?Notificacion {
        $idTipo = TipoNotificacion::query()
            ->where('tipo_notificacion', $tipo)
            ->value('id_tipo_notificacion');

        if (! $idTipo) {
            return null;
        }

        return Notificacion::create([
            'id_usuario' => $idUsuario,
            'id_tipo_notificacion' => $idTipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'leida' => false,
            'fecha_creacion' => now(),
        ]);
    }

    /**
     * Envía una notificación a todos los administradores activos.
     */
    public function crearParaAdministradores(
        string $tipo,
        string $titulo,
        string $mensaje
    ): int {
        $administradores = User::query()
            ->where('es_administrador', true)
            ->where('activo', true)
            ->pluck('id_usuario');

        $creadas = 0;

        foreach ($administradores as $idUsuario) {
            if ($this->crear((int) $idUsuario, $tipo, $titulo, $mensaje)) {
                $creadas++;
            }
        }

        return $creadas;
    }
}
