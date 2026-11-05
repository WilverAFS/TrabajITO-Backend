<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisponibilidadResource extends JsonResource
{
    private const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    private function formatearHora($hora): ?string
    {
        if ($hora === null) {
            return null;
        }

        $hora = substr((string) $hora, 0, 8);

        return strlen($hora) === 5
            ? $hora . ':00'
            : $hora;
    }

    public function toArray(Request $request): array
    {
        $dia = (int) $this->dia_semana;

        return [
            'id_disponibilidad' => $this->id_disponibilidad,

            'dia_semana' => $dia,

            'dia_nombre' => self::DIAS[$dia] ?? null,

            'hora_inicio' => $this->formatearHora(
                $this->hora_inicio
            ),

            'hora_fin' => $this->formatearHora(
                $this->hora_fin
            ),

            'activo' => (bool) $this->activo,
        ];
    }
}
