<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContratacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cliente = $this->relationLoaded('cliente')
            ? $this->cliente
            : null;

        $trabajador = $this->relationLoaded('trabajador')
            ? $this->trabajador
            : null;

        $usuarioTrabajador = $trabajador
            && $trabajador->relationLoaded('usuario')
                ? $trabajador->usuario
                : null;

        $estado = $this->relationLoaded('estado')
            ? $this->estado
            : null;

        $esCreacion = $request->isMethod('post');

        $esDetalle = $request->route('id_contratacion') !== null;

        $datos = [
            'id_contratacion' => $this->id_contratacion,
            'descripcion_servicio' => $this->descripcion_servicio,
        ];

        if (!$esCreacion) {
            $datos['cliente'] = $cliente ? [
                'id_usuario' => $cliente->id_usuario,
                'nombre_completo' => trim(
                    $cliente->nombre . ' ' . $cliente->apellido
                ),
            ] : null;

            $datos['trabajador'] = $trabajador ? [
                'id_trabajador' => $trabajador->id_trabajador,
                'nombre_completo' => $usuarioTrabajador
                    ? trim(
                        $usuarioTrabajador->nombre . ' ' .
                        $usuarioTrabajador->apellido
                    )
                    : null,
            ] : null;

            if ($esDetalle && $trabajador) {
                $datos['trabajador']['telefono'] =
                    $usuarioTrabajador?->telefono;

                $datos['trabajador']['oficios'] =
                    $trabajador->relationLoaded('oficios')
                        ? $trabajador->oficios->map(
                            fn ($oficio) => [
                                'id_oficio' => $oficio->id_oficio,
                                'nombre' => $oficio->nombre,
                            ]
                        )->values()->all()
                        : [];
            }
        }

        $datos['estado'] = $estado ? [
            'id_estado_contratacion' =>
                $estado->id_estado_contratacion,
            'estado_contratacion' =>
                $estado->estado_contratacion,
        ] : null;

        $datos['fecha_solicitud'] =
            $this->fecha_solicitud?->toIso8601String();

        if ($esCreacion) {
            return $datos;
        }

        $datos['fecha_respuesta'] =
            $this->fecha_respuesta?->toIso8601String();

        $datos['fecha_finalizacion'] =
            $this->fecha_finalizacion?->toIso8601String();

        if ($esDetalle) {
            $publicacion = $this->relationLoaded('publicacion')
                ? $this->publicacion
                : null;

            $datos['publicacion'] = $publicacion ? [
                'id_publicacion' => $publicacion->id_publicacion,
            ] : null;
        }

        $datos['acciones_disponibles'] =
            $this->accionesDisponibles($request, $trabajador);

        return $datos;
    }

    private function accionesDisponibles(
        Request $request,
        $trabajador
    ): array {
        $usuario = $request->user();

        if (!$usuario) {
            return [];
        }

        $esCliente =
            (int) $this->id_cliente ===
            (int) $usuario->id_usuario;

        $esTrabajador = $trabajador &&
            (int) $trabajador->id_usuario ===
            (int) $usuario->id_usuario;

        $estado = (int) $this->id_estado_contratacion;

        if ($estado === 1) {
            if ($esTrabajador) {
                return ['aceptar', 'rechazar'];
            }

            return $esCliente ? ['cancelar'] : [];
        }

        if ($estado === 2) {
            if ($esTrabajador) {
                return ['iniciar', 'cancelar'];
            }

            return $esCliente ? ['cancelar'] : [];
        }

        if ($estado === 4) {
            if ($esTrabajador) {
                return ['finalizar', 'cancelar'];
            }

            return $esCliente ? ['cancelar'] : [];
        }

        if ($estado === 5 && $esTrabajador) {
            $tienePublicacion = $this->relationLoaded('publicacion')
                && $this->publicacion !== null;

            return $tienePublicacion
                ? []
                : ['crear_publicacion'];
        }

        return [];
    }
}
