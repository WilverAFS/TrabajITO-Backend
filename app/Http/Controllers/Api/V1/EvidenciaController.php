<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CargarEvidenciasRequest;
use App\Http\Resources\Api\V1\EvidenciaResource;
use App\Models\Evidencia;
use App\Models\Publicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EvidenciaController extends Controller
{
    /**
     * POST
     * /publicaciones/{id_publicacion}/evidencias
     */
    public function store(
        CargarEvidenciasRequest $request,
        int $id_publicacion
    ): JsonResponse|AnonymousResourceCollection {

        $publicacion = Publicacion::query()
            ->with([
                'contratacion.trabajador',
                'evidencias',
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

        $esPropietario =
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

        if (! $esPropietario) {
            return $this->error(
                'No tienes permiso para administrar evidencias de esta publicación.',
                'EVIDENCE_ACCESS_DENIED',
                403
            );
        }

        if (
            (int) $publicacion
                ->contratacion
                ->id_estado_contratacion !== 5
        ) {
            return $this->error(
                'La contratación asociada no está finalizada.',
                'HIRING_NOT_FINISHED',
                409
            );
        }

        $imagenesActuales =
            $publicacion
                ->evidencias
                ->where(
                    'tipo_archivo',
                    'IMAGEN'
                )
                ->count();

        $videosActuales =
            $publicacion
                ->evidencias
                ->where(
                    'tipo_archivo',
                    'VIDEO'
                )
                ->count();

        $nuevasImagenes = 0;
        $nuevosVideos = 0;

        foreach (
            $request->file('archivos')
            as $archivo
        ) {

            if (
                str_starts_with(
                    $archivo->getMimeType(),
                    'image/'
                )
            ) {
                $nuevasImagenes++;
            } else {
                $nuevosVideos++;
            }
        }

        if (
            ($imagenesActuales + $nuevasImagenes) > 10
        ) {
            return $this->error(
                'Se excedió el límite de 10 imágenes por publicación.',
                'IMAGE_LIMIT_EXCEEDED',
                409
            );
        }

        if (
            ($videosActuales + $nuevosVideos) > 3
        ) {
            return $this->error(
                'Se excedió el límite de 3 videos por publicación.',
                'VIDEO_LIMIT_EXCEEDED',
                409
            );
        }

        $evidencias = DB::transaction(
            function () use (
                $request,
                $publicacion
            ) {

                $creadas = collect();

                foreach (
                    $request->file('archivos')
                    as $archivo
                ) {

                    $tipoArchivo =
                        str_starts_with(
                            $archivo->getMimeType(),
                            'image/'
                        )
                        ? 'IMAGEN'
                        : 'VIDEO';

                    $ruta =
                        $archivo->store(
                            'publicaciones',
                            'public'
                        );

                    $evidencia =
                        Evidencia::create([
                            'id_publicacion' =>
                                $publicacion
                                    ->id_publicacion,

                            'tipo_archivo' =>
                                $tipoArchivo,

                            'ruta_archivo' =>
                                $ruta,

                            'fecha_carga' =>
                                now(),
                        ]);

                    $creadas->push(
                        $evidencia
                    );
                }

                return $creadas;
            }
        );

        return EvidenciaResource::collection(
            $evidencias
        )->response()->setStatusCode(201);
    }

    /**
     * DELETE
     * /evidencias/{id_evidencia}
     */
    public function destroy(
        Request $request,
        int $id_evidencia
    ) {

        $evidencia = Evidencia::query()
            ->with([
                'publicacion.contratacion.trabajador',
            ])
            ->find($id_evidencia);

        if (! $evidencia) {
            return $this->error(
                'Evidencia no encontrada.',
                'EVIDENCE_NOT_FOUND',
                404
            );
        }

        $usuario = $request->user();

        $esAdministrador =
            (bool) $usuario->es_administrador;

        $esPropietario =
            $evidencia->publicacion &&
            $evidencia
                ->publicacion
                ->contratacion &&
            $evidencia
                ->publicacion
                ->contratacion
                ->trabajador &&
            (
                (int)
                $evidencia
                    ->publicacion
                    ->contratacion
                    ->trabajador
                    ->id_usuario
                ===
                (int)
                $usuario->id_usuario
            );

        if (
            ! $esAdministrador &&
            ! $esPropietario
        ) {
            return $this->error(
                'No tienes permiso para eliminar esta evidencia.',
                'EVIDENCE_ACCESS_DENIED',
                403
            );
        }

        DB::transaction(
            function () use (
                $evidencia
            ) {

                Storage::disk('public')
                    ->delete(
                        $evidencia
                            ->ruta_archivo
                    );

                $evidencia->delete();
            }
        );

        return response()->noContent();
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