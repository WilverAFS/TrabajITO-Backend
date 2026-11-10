<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CrearValoracionRequest;
use App\Http\Requests\Api\V1\ActualizarValoracionRequest;
use App\Http\Resources\Api\V1\ValoracionResource;
use App\Models\Publicacion;
use App\Models\Valoracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ValoracionController extends Controller
{
    /**
     * POST /api/v1/publicaciones/{id_publicacion}/valoracion
     */
    public function store(
        CrearValoracionRequest $request,
        int $id_publicacion
    ): JsonResponse {
        return DB::transaction(function () use (
            $request,
            $id_publicacion
        ): JsonResponse {
            $publicacion = Publicacion::query()
                ->lockForUpdate()
                ->with([
                    'contratacion',
                    'valoracion',
                ])
                ->find($id_publicacion);

            if (! $publicacion) {
                return $this->error(
                    'Publicación no encontrada.',
                    'PUBLICATION_NOT_FOUND',
                    404
                );
            }

            $contratacion = $publicacion->contratacion;

            if (! $contratacion) {
                return $this->error(
                    'La publicación no tiene una contratación válida.',
                    'HIRING_NOT_FOUND',
                    404
                );
            }

            $usuario = $request->user();

            // Solo puede valorar el cliente de la contratación.
            if (
                (int) $contratacion->id_cliente !==
                (int) $usuario->id_usuario
            ) {
                return $this->error(
                    'No tienes permiso para valorar esta publicación.',
                    'RATING_ACCESS_DENIED',
                    403
                );
            }

            // Estado 5: Finalizada.
            if (
                (int) $contratacion->id_estado_contratacion !== 5
            ) {
                return $this->error(
                    'La contratación debe estar finalizada para poder valorar.',
                    'HIRING_NOT_FINISHED',
                    409
                );
            }

            // Solo se permite una valoración por publicación.
            if ($publicacion->valoracion) {
                return $this->error(
                    'Esta publicación ya tiene una valoración registrada.',
                    'RATING_ALREADY_EXISTS',
                    409
                );
            }

            $datos = $request->validated();

            $valoracion = Valoracion::create([
                'id_publicacion' => $publicacion->id_publicacion,
                'id_cliente' => $usuario->id_usuario,
                'calificacion' => $datos['calificacion'],
                'comentario' => $datos['comentario'] ?? null,
                'fecha_valoracion' => now(),
            ]);

            $valoracion->load('cliente');

            return response()->json([
                'success' => true,
                'message' => 'Valoración registrada correctamente.',
                'data' => new ValoracionResource($valoracion),
            ], 201);
        });
    }

    /**
     * PATCH /api/v1/valoraciones/{id_valoracion}
     */
    public function update(
        ActualizarValoracionRequest $request,
        int $id_valoracion
    ): JsonResponse {
        $valoracion = Valoracion::query()
            ->with('publicacion')
            ->find($id_valoracion);

        if (! $valoracion) {
            return $this->error(
                'Valoración no encontrada.',
                'RATING_NOT_FOUND',
                404
            );
        }

        $usuario = $request->user();

        // Solo el autor puede actualizar su valoración.
        if (
            (int) $valoracion->id_cliente !==
            (int) $usuario->id_usuario
        ) {
            return $this->error(
                'No tienes permiso para actualizar esta valoración.',
                'RATING_ACCESS_DENIED',
                403
            );
        }

        // La publicación debe existir y seguir visible.
        if (
            ! $valoracion->publicacion ||
            ! $valoracion->publicacion->visible
        ) {
            return $this->error(
                'No se puede actualizar la valoración porque la publicación no está disponible.',
                'PUBLICATION_NOT_VISIBLE',
                409
            );
        }

        $datos = $request->validated();

        if (empty($datos)) {
            return $this->error(
                'Debes enviar al menos un campo para actualizar.',
                'VALIDATION_ERROR',
                422
            );
        }

        $valoracion->update($datos);

        $valoracion->refresh()->load('cliente');

        return response()->json([
            'success' => true,
            'message' => 'Valoración actualizada correctamente.',
            'data' => new ValoracionResource($valoracion),
        ], 200);
    }

    /**
     * Formato general de errores.
     */
    private function error(
        string $mensaje,
        string $codigo,
        int $estado
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
            'error_code' => $codigo,
            'errors' => null,
        ], $estado);
    }
}
