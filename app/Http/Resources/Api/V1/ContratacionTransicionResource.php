<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContratacionTransicionResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id_contratacion' =>
                $this->id_contratacion,

            'estado' => [
                'id_estado_contratacion' =>
                    $this->estado->id_estado_contratacion,

                'estado_contratacion' =>
                    $this->estado->estado_contratacion,
            ],

            'fecha_respuesta' =>
                $this->fecha_respuesta?->toIso8601String(),

            'fecha_finalizacion' =>
                $this->fecha_finalizacion?->toIso8601String(),

            'acciones_disponibles' =>
                $this->accionesDisponibles(),
        ];
    }

    private function accionesDisponibles(): array
    {
        return match (
            (int) $this->id_estado_contratacion
        ) {
            1 => [
                'aceptar',
                'rechazar',
                'cancelar',
            ],

            2 => [
                'iniciar',
                'cancelar',
            ],

            4 => [
                'finalizar',
                'cancelar',
            ],

            5 => [
                'crear_publicacion',
            ],

            default => [],
        };
    }
}