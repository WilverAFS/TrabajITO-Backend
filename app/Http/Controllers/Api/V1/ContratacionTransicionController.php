<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ContratacionTransicionResource;
use App\Models\Contratacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContratacionTransicionController extends Controller
{
    public function aceptar(
        Request $request,
        Contratacion $contratacion
    ): JsonResponse {
        return $this->transicionar(
            $request,
            $contratacion,
            1,
            2,
            true
        );
    }

    public function rechazar(
        Request $request,
        Contratacion $contratacion
    ): JsonResponse {
        return $this->transicionar(
            $request,
            $contratacion,
            1,
            3,
            true
        );
    }

    public function iniciar(
        Request $request,
        Contratacion $contratacion
    ): JsonResponse {
        return $this->transicionar(
            $request,
            $contratacion,
            2,
            4,
            true
        );
    }

    public function finalizar(
        Request $request,
        Contratacion $contratacion
    ): JsonResponse {
        return $this->transicionar(
            $request,
            $contratacion,
            4,
            5,
            true
        );
    }

    public function cancelar(
        Request $request,
        Contratacion $contratacion
    ): JsonResponse {
        $usuario = $request->user();

        $contratacion->load(
            'trabajador',
            'estado'
        );

        $esCliente =
            (int) $contratacion->id_cliente ===
            (int) $usuario->id_usuario;

        $esTrabajador =
            $contratacion->trabajador &&
            (int) $contratacion->trabajador->id_usuario ===
            (int) $usuario->id_usuario;

        if (! $esCliente && ! $esTrabajador) {
            return $this->error(
                'No tienes permiso para modificar esta contratación.',
                'HIRING_ACCESS_DENIED',
                403
            );
        }

        if (
            ! in_array(
                (int) $contratacion->id_estado_contratacion,
                [1, 2, 4]
            )
        ) {
            return $this->error(
                'La contratación no puede cancelarse en su estado actual.',
                'INVALID_STATE_TRANSITION',
                409
            );
        }

        $contratacion->update([
            'id_estado_contratacion' => 6,
        ]);

        $contratacion->load('estado');

        return response()->json([
            'success' => true,
            'message' =>
                'Contratación cancelada correctamente.',
            'data' =>
                new ContratacionTransicionResource(
                    $contratacion
                ),
        ]);
    }

    private function transicionar(
        Request $request,
        Contratacion $contratacion,
        int $estadoOrigen,
        int $estadoDestino,
        bool $requiereTrabajador
    ): JsonResponse {
        $usuario = $request->user();

        $contratacion->load(
            'trabajador',
            'estado'
        );

        if ($requiereTrabajador) {
            $esTrabajador =
                $contratacion->trabajador &&
                (int) $contratacion->trabajador->id_usuario ===
                (int) $usuario->id_usuario;

            if (! $esTrabajador) {
                return $this->error(
                    'No tienes permiso para realizar esta acción.',
                    'HIRING_ACCESS_DENIED',
                    403
                );
            }
        }

        if (
            (int) $contratacion->id_estado_contratacion !==
            $estadoOrigen
        ) {
            return $this->error(
                'La transición solicitada no es válida.',
                'INVALID_STATE_TRANSITION',
                409
            );
        }

        $contratacion->id_estado_contratacion =
            $estadoDestino;

        if (
            in_array(
                $estadoDestino,
                [2, 3]
            )
        ) {
            $contratacion->fecha_respuesta =
                now();
        }

        if ($estadoDestino === 5) {
            $contratacion->fecha_finalizacion =
                now();
        }

        $contratacion->save();

        $contratacion->refresh();

        $contratacion->load('estado');

        return response()->json([
            'success' => true,
            'message' =>
                'Estado de contratación actualizado correctamente.',
            'data' =>
                new ContratacionTransicionResource(
                    $contratacion
                ),
        ]);
    }

    private function error(
        string $mensaje,
        string $codigo,
        int $estado
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
            'error_code' => $codigo,
        ], $estado);
    }
}