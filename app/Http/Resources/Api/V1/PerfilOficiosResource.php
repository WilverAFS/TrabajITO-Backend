<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilOficiosResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_trabajador' => $this->id_trabajador,

            'oficios' => PerfilOficioResource::collection(
                $this->whenLoaded('oficios')
            ),
        ];
    }
}