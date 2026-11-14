<?php

namespace App\Observers;

use App\Models\Reporte;
use App\Services\NotificacionService;

class ReporteObserver
{
    public function created(Reporte $reporte): void
    {
        $datos = match ($reporte->tipo_objetivo) {
            'PUBLICACION' => [
                'Nueva publicación reportada',
                'Se recibió un reporte relacionado con una publicación.',
            ],
            'PERFIL' => [
                'Nuevo perfil reportado',
                'Se recibió un reporte relacionado con un perfil profesional.',
            ],
            default => null,
        };

        if (! $datos) {
            return;
        }

        app(NotificacionService::class)->crearParaAdministradores(
            'REPORTE',
            $datos[0],
            $datos[1]
        );
    }
}
