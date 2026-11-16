<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminReporteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_reporte' => $this->id_reporte,

            'tipo_objetivo' => $this->tipo_objetivo,
            'id_objetivo' => $this->id_objetivo,

            'motivo' => $this->motivo,
            'descripcion' => $this->descripcion,

            'estado' => $this->relationLoaded('estado') && $this->estado
                ? [
                    'id_estado_reporte' => $this->estado->id_estado_reporte,
                    'estado_reporte' => $this->estado->estado_reporte,
                ]
                : null,

            'denunciante' => $this->relationLoaded('denunciante')
                && $this->denunciante
                ? [
                    'id_usuario' => $this->denunciante->id_usuario,
                    'nombre_completo' => trim(
                        $this->denunciante->nombre . ' ' .
                        $this->denunciante->apellido
                    ),
                ]
                : null,

            'administrador_moderador' => $this->relationLoaded('moderador')
                && $this->moderador
                ? [
                    'id_usuario' => $this->moderador->id_usuario,
                    'nombre_completo' => trim(
                        $this->moderador->nombre . ' ' .
                        $this->moderador->apellido
                    ),
                ]
                : null,

            'resolucion' => $this->resolucion,

            'fecha_creacion' => $this->fecha_creacion
                ?->toIso8601String(),

            'fecha_resolucion' => $this->fecha_resolucion
                ?->toIso8601String(),
        ];
    }
}