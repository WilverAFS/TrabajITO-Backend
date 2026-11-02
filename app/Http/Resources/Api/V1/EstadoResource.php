<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstadoResource extends JsonResource
{
    /**
     * Transformar el estado para la respuesta de la API.
     */
    public function toArray(Request $request): array
    {
        return [
            'id_estado' => $this->id_estado,
            'estado' => $this->estado,
        ];
    }
}