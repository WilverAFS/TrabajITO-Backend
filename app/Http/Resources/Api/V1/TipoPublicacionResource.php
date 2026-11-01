<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoPublicacionResource extends JsonResource
{
    /**
     * Transformar el tipo de publicación para la respuesta de la API.
     */
    public function toArray(Request $request): array
    {
        return [
            'id_tipo_publicacion' => $this->id_tipo_publicacion,
            'tipo_publicacion' => $this->tipo_publicacion,
        ];
    }
}