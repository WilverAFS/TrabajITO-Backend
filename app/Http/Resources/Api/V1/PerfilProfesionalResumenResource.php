<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilProfesionalResumenResource extends JsonResource
{
    /**
     * Representación pública resumida de un trabajador.
     */
    public function toArray(Request $request): array
    {
        $usuario = $this->relationLoaded('usuario')
            ? $this->usuario
            : null;

        $municipio = $this->relationLoaded('municipio')
            ? $this->municipio
            : null;

        $estado = $municipio && $municipio->relationLoaded('estado')
            ? $municipio->estado
            : null;

        $oficios = $this->relationLoaded('oficios')
            ? $this->oficios
            : collect();

        $calificacion = $this->getAttribute('calificacion_promedio');

        return [
            'id_trabajador' => $this->id_trabajador,

            'nombre_completo' => trim(
                ($usuario?->nombre ?? '') . ' ' .
                ($usuario?->apellido ?? '')
            ),

            'descripcion' => $this->descripcion,

            'municipio' => $municipio ? [
                'id_municipio' => $municipio->id_municipio,
                'municipio' => $municipio->municipio,
            ] : null,

            'estado' => $estado ? [
                'id_estado' => $estado->id_estado,
                'estado' => $estado->estado,
            ] : null,

            'oficios' => $oficios->map(function ($oficio) {
                return [
                    'id_oficio' => $oficio->id_oficio,
                    'nombre' => $oficio->nombre,
                ];
            })->values()->all(),

            'calificacion_promedio' => $calificacion !== null
                ? round((float) $calificacion, 2)
                : null,

            'total_valoraciones' => (int) (
                $this->getAttribute('total_valoraciones') ?? 0
            ),
        ];
    }
}
