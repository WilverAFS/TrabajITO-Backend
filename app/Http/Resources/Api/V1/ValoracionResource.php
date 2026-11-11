<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValoracionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_valoracion' =>
                $this->id_valoracion,

            'calificacion' =>
                $this->calificacion,

            'comentario' =>
                $this->comentario,

            'fecha_valoracion' =>
                $this->fecha_valoracion
                    ?->toIso8601String(),

            'cliente' =>
                $this->relationLoaded('cliente') &&
                $this->cliente
                    ? [
                        'id_usuario' =>
                            $this->cliente->id_usuario,

                        'nombre_completo' =>
                            trim(
                                $this->cliente->nombre
                                . ' ' .
                                $this->cliente->apellido
                            ),
                    ]
                    : null,
        ];
    }
}
