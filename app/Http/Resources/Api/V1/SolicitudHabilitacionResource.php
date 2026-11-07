<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudHabilitacionResource extends JsonResource
{
    public function __construct(
        $resource,
        private bool $resumido = false
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $datos = [
            'id_solicitud' => $this->id_solicitud,
            'estado' => [
                'id_estado_solicitud' => $this->estadoSolicitud->id_estado_solicitud,
                'estado_solicitud' => $this->estadoSolicitud->estado_solicitud,
            ],
            'fecha_solicitud' => $this->fecha_solicitud?->toIso8601String(),
        ];

        // Respuesta reducida al crear una solicitud
        if ($this->resumido) {
            return $datos;
        }

        // Respuesta completa para el historial
        return [
            'id_solicitud' => $datos['id_solicitud'],
            'estado' => $datos['estado'],
            'administrador_revisor' => $this->administradorRevisor
                ? [
                    'id_usuario' => $this->administradorRevisor->id_usuario,
                    'nombre_completo' => trim(
                        $this->administradorRevisor->nombre . ' ' .
                        $this->administradorRevisor->apellido
                    ),
                ]
                : null,
            'comentario_revision' => $this->comentario_revision,
            'fecha_solicitud' => $datos['fecha_solicitud'],
            'fecha_revision' => $this->fecha_revision?->toIso8601String(),
        ];
    }
}
