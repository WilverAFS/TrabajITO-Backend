<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReporteResource extends JsonResource
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

            'fecha_creacion' => $this->fecha_creacion
                ?->toIso8601String(),
        ];
    }
}
