<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicacionResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [

            'id_publicacion' =>
            $this->id_publicacion,

            'titulo' =>
            $this->titulo,

            'descripcion' =>
            $this->descripcion,

            'visible' =>
            $this->visible,

            'fecha_publicacion' =>
            $this->fecha_publicacion
                ?->toIso8601String(),

            'tipo' =>
            $this->relationLoaded('tipoPublicacion') ? [
                'id_tipo_publicacion' =>
                $this->tipoPublicacion
                    ->id_tipo_publicacion,

                'tipo_publicacion' =>
                $this->tipoPublicacion
                    ->tipo_publicacion,
            ]
                : null,

            'contratacion' =>
            $this->relationLoaded(
                'contratacion'
            )
                ? [
                    'id_contratacion' =>
                    $this->contratacion
                        ->id_contratacion,
                ]
                : null,

            'trabajador' =>
            $this->relationLoaded(
                'contratacion'
            ) &&
                $this->contratacion?->relationLoaded(
                    'trabajador'
                ) &&
                $this->contratacion
                ?->trabajador
                ?->relationLoaded(
                    'usuario'
                )
                ? [
                    'id_trabajador' =>
                    $this->contratacion
                        ->trabajador
                        ->id_trabajador,

                    'nombre_completo' =>
                    trim(
                        $this->contratacion
                            ->trabajador
                            ->usuario
                            ->nombre
                            . ' ' .
                            $this->contratacion
                            ->trabajador
                            ->usuario
                            ->apellido
                    ),
                ]
                : null,

            'evidencias' =>
            $this->relationLoaded(
                'evidencias'
            )
                ? EvidenciaResource::collection(
                    $this->evidencias
                )
                : [],

            'valoracion' =>
            $this->relationLoaded('valoracion') && $this->valoracion ? [
                'id_valoracion' =>
                $this->valoracion
                    ->id_valoracion,

                'calificacion' =>
                $this->valoracion
                    ->calificacion,

                'comentario' =>
                $this->valoracion
                    ->comentario,

                'fecha_valoracion' =>
                $this->valoracion
                    ->fecha_valoracion
                    ?->toIso8601String(),

            ]
                : null,

            'total_evidencias' =>
            $this->relationLoaded('evidencias') ?
                $this->evidencias->count()
                : 0,
        ];
    }
}
