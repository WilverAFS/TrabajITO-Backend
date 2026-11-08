<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ActualizarPublicacionRequest;
use App\Http\Requests\Api\V1\CrearPublicacionRequest;
use App\Http\Resources\Api\V1\PublicacionResource;
use App\Models\Contratacion;
use App\Models\Publicacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class PublicacionController extends Controller
{
    /**
     * POST
     * /contrataciones/{id_contratacion}/publicacion
     */
    public function store(
        CrearPublicacionRequest $request,
        int $id_contratacion
    ): JsonResponse {

        $usuario = $request->user();

        $contratacion = Contratacion::query()
            ->with([
                'trabajador',
                'publicacion',
            ])
            ->find($id_contratacion);

        if (! $contratacion) {
            return $this->error(
                'Contratación no encontrada.',
                'HIRING_NOT_FOUND',
                404
            );
        }

        $esTrabajador =
            $contratacion->trabajador &&
            (
                (int) $contratacion
                    ->trabajador
                    ->id_usuario
                ===
                (int) $usuario->id_usuario
            );

        if (! $esTrabajador) {
            return $this->error(
                'No tienes permiso para crear una publicación para esta contratación.',
                'PUBLICATION_ACCESS_DENIED',
                403
            );
        }

        if (
            (int) $contratacion
                ->id_estado_contratacion !== 5
        ) {
            return $this->error(
                'La contratación aún no está finalizada.',
                'HIRING_NOT_FINISHED',
                409
            );
        }

        if ($contratacion->publicacion) {
            return $this->error(
                'La contratación ya tiene una publicación.',
                'PUBLICATION_ALREADY_EXISTS',
                409
            );
        }

        $datos = $request->validated();

        $publicacion = Publicacion::create([
            'id_contratacion' =>
            $contratacion->id_contratacion,

            'id_tipo_publicacion' =>
            $datos['id_tipo_publicacion'],

            'titulo' =>
            $datos['titulo'],

            'descripcion' =>
            $datos['descripcion'],

            'visible' => true,

            'fecha_publicacion' => now(),
        ]);

        $publicacion->load([
            'tipoPublicacion',
            'contratacion.trabajador.usuario',
            'evidencias',
            'valoracion',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            'Publicación creada correctamente.',
            'data' =>
            new PublicacionResource(
                $publicacion
            ),
        ], 201);
    }

    /**
     * GET
     * /publicaciones/{id_publicacion}
     */
    public function show(
        Request $request,
        int $id_publicacion
    ): JsonResponse {

        $publicacion = Publicacion::query()
            ->with([
                'tipoPublicacion',
                'contratacion.trabajador.usuario',
                'evidencias',
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

        $usuario = $request->user();

        if (! $usuario) {

            $token = $request->bearerToken();

            if ($token) {

                $accessToken =
                    PersonalAccessToken::findToken(
                        $token
                    );

                $usuario =
                    $accessToken?->tokenable;
            }
        }

        $esAdministrador =
            $usuario &&
            (bool) $usuario->es_administrador;

        $esPropietario =
            $usuario &&
            $publicacion->contratacion &&
            $publicacion->contratacion->trabajador &&
            (
                (int) $publicacion
                    ->contratacion
                    ->trabajador
                    ->id_usuario
                ===
                (int) $usuario->id_usuario
            );

        if (
            ! $publicacion->visible &&
            ! $esAdministrador &&
            ! $esPropietario
        ) {
            return $this->error(
                'La publicación no está disponible.',
                'PUBLICATION_HIDDEN',
                404
            );
        }

        return response()->json([
            'success' => true,
            'message' =>
            'Publicación obtenida correctamente.',
            'data' =>
            new PublicacionResource(
                $publicacion
            ),
        ]);
    }

    /**
     * PATCH
     * /publicaciones/{id_publicacion}
     */
    public function update(
        ActualizarPublicacionRequest $request,
        int $id_publicacion
    ): JsonResponse {

        $publicacion = Publicacion::query()
            ->with([
                'contratacion.trabajador',
            ])
            ->find($id_publicacion);

        if (! $publicacion) {
            return $this->error(
                'Publicación no encontrada.',
                'PUBLICATION_NOT_FOUND',
                404
            );
        }

        $esPropietario =
            $publicacion->contratacion &&
            $publicacion->contratacion->trabajador &&
            (
                (int) $publicacion
                    ->contratacion
                    ->trabajador
                    ->id_usuario
                ===
                (int) $request->user()
                    ->id_usuario
            );

        if (! $esPropietario) {
            return $this->error(
                'No tienes permiso para modificar esta publicación.',
                'PUBLICATION_ACCESS_DENIED',
                403
            );
        }

        $publicacion->update(
            $request->validated()
        );

        $publicacion->load([
            'tipoPublicacion',
            'contratacion.trabajador.usuario',
            'evidencias',
            'valoracion',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            'Publicación actualizada correctamente.',
            'data' =>
            new PublicacionResource(
                $publicacion
            ),
        ]);
    }

    /**
     * PATCH
     * /admin/publicaciones/{id_publicacion}/ocultar
     */
    public function ocultar(
        Request $request,
        int $id_publicacion
    ): JsonResponse {

        if (
            ! (bool) $request->user()
                ->es_administrador
        ) {
            return $this->error(
                'Acción reservada para administradores.',
                'ADMIN_REQUIRED',
                403
            );
        }

        $publicacion = Publicacion::find(
            $id_publicacion
        );

        if (! $publicacion) {
            return $this->error(
                'Publicación no encontrada.',
                'PUBLICATION_NOT_FOUND',
                404
            );
        }

        if (! $publicacion->visible) {
            return $this->error(
                'La publicación ya se encuentra oculta.',
                'PUBLICATION_ALREADY_HIDDEN',
                409
            );
        }

        $publicacion->update([
            'visible' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            'Publicación ocultada correctamente.',
            'data' => [
                'id_publicacion' =>
                $publicacion->id_publicacion,

                'visible' => false,
            ],
        ]);
    }

    /**
     * GET
     * /me/perfil/publicaciones
     */
    public function misPublicaciones(
        Request $request
    ): JsonResponse {

        $idUsuario =
            $request->user()->id_usuario;

        $publicaciones = Publicacion::query()
            ->with([
                'tipoPublicacion',
                'contratacion.trabajador.usuario',
                'evidencias',
                'valoracion',
            ])
            ->whereHas(
                'contratacion.trabajador',
                function (
                    Builder $query
                ) use ($idUsuario) {

                    $query->where(
                        'id_usuario',
                        $idUsuario
                    );
                }
            )
            ->orderByDesc(
                'fecha_publicacion'
            )
            ->get();

        return response()->json([
            'success' => true,
            'message' =>
            'Publicaciones obtenidas correctamente.',
            'data' =>
            PublicacionResource::collection(
                $publicaciones
            ),
        ]);
    }

    /**
     * GET
     * /publicaciones/trabajador/{id_trabajador}
     */
    public function publicacionesPorTrabajador(
        int $id_trabajador
    ): JsonResponse {

        $publicaciones = Publicacion::query()
            ->with([
                'tipoPublicacion',
                'contratacion.trabajador.usuario',
                'evidencias',
                'valoracion',
            ])
            ->visibles()
            ->whereHas(
                'contratacion',
                function (
                    Builder $query
                ) use (
                    $id_trabajador
                ) {

                    $query->where(
                        'id_trabajador',
                        $id_trabajador
                    );
                }
            )
            ->orderByDesc(
                'fecha_publicacion'
            )
            ->get();

        return response()->json([
            'success' => true,
            'message' =>
            'Publicaciones obtenidas correctamente.',
            'data' =>
            PublicacionResource::collection(
                $publicaciones
            ),
        ]);
    }

    private function error(
        string $mensaje,
        string $codigo,
        int $status
    ): JsonResponse {

        return response()->json([
            'success' => false,
            'message' => $mensaje,
            'error_code' => $codigo,
        ], $status);
    }
}
