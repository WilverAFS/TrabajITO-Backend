<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSolicitudHabilitacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $perfil = $this->perfilProfesional;

        return [
            'id_solicitud' => (int) $this->id_solicitud,

            'perfil' => $perfil ? [
                'id_trabajador' => (int) $perfil->id_trabajador,
                'nombre_completo' => trim(
                    ($perfil->usuario?->nombre ?? '') . ' ' .
                    ($perfil->usuario?->apellido ?? '')
                ),
                'descripcion' => $perfil->descripcion,
                'municipio' => $perfil->municipio?->municipio,

                'oficios' => $perfil->oficios
                    ->map(fn ($oficio) => [
                        'id_oficio' => (int) $oficio->id_oficio,
                        'nombre' => $oficio->nombre,
                    ])
                    ->values()
                    ->all(),

                'certificados' => $perfil->certificados
                    ->map(fn ($certificado) => [
                        'id_certificado' => (int) $certificado->id_certificado,
                    ])
                    ->values()
                    ->all(),
            ] : null,

            'estado' => [
                'id_estado_solicitud' =>
                    (int) $this->estadoSolicitud->id_estado_solicitud,
                'estado_solicitud' =>
                    $this->estadoSolicitud->estado_solicitud,
            ],

            'administrador_revisor' => $this->administradorRevisor ? [
                'id_usuario' =>
                    (int) $this->administradorRevisor->id_usuario,
                'nombre_completo' => trim(
                    $this->administradorRevisor->nombre . ' ' .
                    $this->administradorRevisor->apellido
                ),
            ] : null,

            'comentario_revision' => $this->comentario_revision,
            'fecha_solicitud' =>
                $this->fecha_solicitud?->toIso8601String(),
            'fecha_revision' =>
                $this->fecha_revision?->toIso8601String(),
        ];
    }
}
