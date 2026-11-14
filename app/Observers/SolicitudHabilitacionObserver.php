<?php

namespace App\Observers;

use App\Models\PerfilProfesional;
use App\Models\SolicitudHabilitacion;
use App\Services\NotificacionService;

class SolicitudHabilitacionObserver
{
    public function created(SolicitudHabilitacion $solicitud): void
    {
        $idUsuario = PerfilProfesional::query()
            ->whereKey($solicitud->id_trabajador)
            ->value('id_usuario');

        if ($idUsuario) {
            app(NotificacionService::class)->crear(
                (int) $idUsuario,
                'SOLICITUD',
                'Solicitud de habilitación enviada',
                'Tu solicitud de habilitación fue enviada correctamente.'
            );
        }
    }

    public function updated(SolicitudHabilitacion $solicitud): void
    {
        if (! $solicitud->wasChanged('id_estado_solicitud')) {
            return;
        }

        $idUsuario = PerfilProfesional::query()
            ->whereKey($solicitud->id_trabajador)
            ->value('id_usuario');

        if (! $idUsuario) {
            return;
        }

        $estado = (int) $solicitud->id_estado_solicitud;
        $datos = match ($estado) {
            3 => [
                'Solicitud de habilitación aprobada',
                'Tu solicitud de habilitación fue aprobada. Ya puedes ofrecer tus servicios.',
            ],
            4 => [
                'Solicitud de habilitación rechazada',
                'Tu solicitud de habilitación fue rechazada. Consulta el comentario de revisión.',
            ],
            default => null,
        };

        if ($datos) {
            app(NotificacionService::class)->crear(
                (int) $idUsuario,
                'SOLICITUD',
                $datos[0],
                $datos[1]
            );
        }
    }
}
