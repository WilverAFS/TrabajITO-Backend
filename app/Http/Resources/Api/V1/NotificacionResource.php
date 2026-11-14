<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_notificacion' => $this->id_notificacion,
            'tipo' => $this->whenLoaded('tipoNotificacion', function () {
                if (! $this->tipoNotificacion) {
                    return null;
                }

                return [
                    'id_tipo_notificacion' => $this->tipoNotificacion->id_tipo_notificacion,
                    'tipo_notificacion' => $this->tipoNotificacion->tipo_notificacion,
                ];
            }),
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'leida' => (bool) $this->leida,
            'fecha_creacion' => $this->fecha_creacion?->toIso8601String(),
        ];
    }
}
