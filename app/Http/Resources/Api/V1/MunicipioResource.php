<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MunicipioResource extends JsonResource
{
    /**
     * Transformar el municipio para la respuesta de la API.
     */
    public function toArray(Request $request): array
    {
        return [
            'id_municipio' => $this->id_municipio,
            'municipio' => $this->municipio,

            'estado' => EstadoResource::make(
                $this->whenLoaded('estado')
            ),
        ];
    }
}