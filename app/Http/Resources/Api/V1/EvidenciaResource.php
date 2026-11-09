<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EvidenciaResource extends JsonResource
{
    /**
     * Transforma la evidencia en un arreglo para la API.
     */
    public function toArray(
        Request $request
    ): array {

        return [
            'id_evidencia' =>
                $this->id_evidencia,

            'tipo_archivo' =>
                $this->tipo_archivo,

            'archivo_url' =>
                Storage::disk('public')->url($this->ruta_archivo),

            'fecha_carga' =>
                $this->fecha_carga
                    ?->toIso8601String(),
        ];
    }
}