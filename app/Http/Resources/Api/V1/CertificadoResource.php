<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_certificado' => $this->id_certificado,

            'nombre' => $this->nombre,

            'institucion_emisora' => $this->institucion_emisora,

            'fecha_emision' => $this->fecha_emision?->format('Y-m-d'),

            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),

            'documento_url' => $this->ruta_documento
                ? '/storage/' . ltrim($this->ruta_documento, '/')
                : null,

            'verificado' => (bool) $this->verificado,
        ];
    }
}
