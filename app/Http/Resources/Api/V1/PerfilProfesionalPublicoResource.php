<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class PerfilProfesionalPublicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $usuario = $this->relationLoaded('usuario')
            ? $this->usuario
            : null;

        $municipio = $this->relationLoaded('municipio')
            ? $this->municipio
            : null;

        $estado = $municipio && $municipio->relationLoaded('estado')
            ? $municipio->estado
            : null;

        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        $oficios = $this->relationLoaded('oficios')
            ? $this->oficios
            : collect();

        $disponibilidades = $this->relationLoaded('disponibilidades')
            ? $this->disponibilidades->where('activo', true)
            : collect();

        $certificados = $this->relationLoaded('certificados')
            ? $this->certificados->where('verificado', true)
            : collect();

        // Esta colección se cargará desde el controlador.
        $publicaciones = $this->relationLoaded('publicacionesPublicas')
            ? $this->getRelation('publicacionesPublicas')
                ->where('visible', true)
            : collect();

        $calificacion = $this->getAttribute('calificacion_promedio');

        return [
            'id_trabajador' => $this->id_trabajador,

            'usuario' => $usuario ? [
                'id_usuario' => $usuario->id_usuario,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'nombre_completo' => trim(
                    $usuario->nombre . ' ' . $usuario->apellido
                ),
                'telefono' => $usuario->telefono,
            ] : null,

            'descripcion' => $this->descripcion,

            'municipio' => $municipio ? [
                'id_municipio' => $municipio->id_municipio,
                'municipio' => $municipio->municipio,
                'estado' => $estado ? [
                    'id_estado' => $estado->id_estado,
                    'estado' => $estado->estado,
                ] : null,
            ] : null,

            'oficios' => $oficios->map(function ($oficio) {
                return [
                    'id_oficio' => $oficio->id_oficio,
                    'nombre' => $oficio->nombre,
                ];
            })->values()->all(),

            'disponibilidades' => $disponibilidades
                ->map(function ($disponibilidad) use ($dias) {
                    return [
                        'id_disponibilidad' =>
                            $disponibilidad->id_disponibilidad,
                        'dia_semana' =>
                            $disponibilidad->dia_semana,
                        'dia_nombre' =>
                            $dias[$disponibilidad->dia_semana] ?? null,
                        'hora_inicio' =>
                            $disponibilidad->hora_inicio,
                        'hora_fin' =>
                            $disponibilidad->hora_fin,
                    ];
                })->values()->all(),

            'certificados' => $certificados
                ->map(function ($certificado) {
                    return [
                        'id_certificado' =>
                            $certificado->id_certificado,
                        'nombre' =>
                            $certificado->nombre,
                        'institucion_emisora' =>
                            $certificado->institucion_emisora,
                        'fecha_emision' =>
                            $certificado->fecha_emision
                                ? Carbon::parse(
                                    $certificado->fecha_emision
                                )->toDateString()
                                : null,
                        'fecha_vencimiento' =>
                            $certificado->fecha_vencimiento
                                ? Carbon::parse(
                                    $certificado->fecha_vencimiento
                                )->toDateString()
                                : null,
                        'verificado' => true,
                    ];
                })->values()->all(),

            'publicaciones' => $publicaciones
                ->map(function ($publicacion) {
                    return [
                        'id_publicacion' =>
                            $publicacion->id_publicacion,
                        'titulo' =>
                            $publicacion->titulo,
                        'descripcion' =>
                            $publicacion->descripcion,
                        'fecha_publicacion' =>
                            $publicacion->fecha_publicacion
                                ? Carbon::parse(
                                    $publicacion->fecha_publicacion
                                )->toIso8601String()
                                : null,
                    ];
                })->values()->all(),

            'calificacion_promedio' => $calificacion !== null
                ? round((float) $calificacion, 2)
                : null,

            'total_valoraciones' => (int) (
                $this->getAttribute('total_valoraciones') ?? 0
            ),
        ];
    }
}
