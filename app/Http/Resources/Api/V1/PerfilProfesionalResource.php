<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilProfesionalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_trabajador' => $this->id_trabajador,
            'id_usuario' => $this->id_usuario,
            'descripcion' => $this->descripcion,
            'activo' => (bool) $this->activo,

            'municipio' => MunicipioResource::make(
                $this->whenLoaded('municipio')
            ),

            'oficios' => PerfilOficioResource::collection(
                $this->whenLoaded('oficios')
            ),
        ];
    }
}