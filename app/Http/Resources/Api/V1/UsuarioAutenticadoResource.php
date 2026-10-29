<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioAutenticadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $perfil = $this->perfilProfesional;

        $tienePerfil = $perfil !== null;

        $habilitado = $tienePerfil
            && $perfil->solicitudesHabilitacion()
                ->whereHas('estadoSolicitud', function ($query) {
                    $query->where(
                        'estado_solicitud',
                        'Aceptada'
                    );
                })
                ->exists();

        return [
            'id_usuario' => $this->id_usuario,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'correo' => $this->correo,
            'telefono' => $this->telefono,
            'es_administrador' => (bool) $this->es_administrador,
            'activo' => (bool) $this->activo,
            'tiene_perfil_profesional' => $tienePerfil,
            'es_trabajador_habilitado' => $habilitado,
            'id_trabajador' => $perfil?->id_trabajador,
        ];
    }
}
