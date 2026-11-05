<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilOficioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_oficio' => $this->id_oficio,
            'nombre' => $this->nombre,
        ];
    }
}