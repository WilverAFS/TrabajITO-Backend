<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    private bool $incluirPerfilProfesional;
    private bool $soloEstado;

    public function __construct(
        $resource,
        bool $incluirPerfilProfesional = false,
        bool $soloEstado = false
    ) {
        parent::__construct($resource);
        $this->incluirPerfilProfesional = $incluirPerfilProfesional;
        $this->soloEstado = $soloEstado;
    }

    public function toArray(Request $request): array
    {
        if ($this->soloEstado) {
            return [
                'id_usuario' => $this->id_usuario,
                'activo' => (bool) $this->activo,
            ];
        }

        $perfil = $this->resource->relationLoaded('perfilProfesional')
            ? $this->resource->perfilProfesional
            : null;

        $tienePerfil = $perfil !== null;
        $habilitado = false;

        if ($perfil && $this->activo && $perfil->activo) {
            $solicitudes = $perfil->relationLoaded('solicitudesHabilitacion')
                ? $perfil->solicitudesHabilitacion
                : $perfil->solicitudesHabilitacion()
                    ->with('estadoSolicitud')
                    ->get();

            $habilitado = $solicitudes->contains(
                fn ($solicitud) => $solicitud->estadoSolicitud?->estado_solicitud === 'Aceptada'
            );
        }

        $datos = [
            'id_usuario' => $this->id_usuario,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => trim("{$this->nombre} {$this->apellido}"),
            'correo' => $this->correo,
            'telefono' => $this->telefono,
            'es_administrador' => (bool) $this->es_administrador,
            'activo' => (bool) $this->activo,
            'fecha_registro' => $this->fecha_registro?->toIso8601String(),
            'tiene_perfil_profesional' => $tienePerfil,
            'es_trabajador_habilitado' => $habilitado,
            'id_trabajador' => $perfil?->id_trabajador,
        ];

        if ($this->incluirPerfilProfesional) {
            $datos['perfil_profesional'] = $perfil
                ? [
                    'id_trabajador' => $perfil->id_trabajador,
                    'activo' => (bool) $perfil->activo,
                ]
                : null;
        }

        return $datos;
    }
}
