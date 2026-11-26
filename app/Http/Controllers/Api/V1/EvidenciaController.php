<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CargarEvidenciasRequest;
use App\Http\Resources\Api\V1\EvidenciaResource;
use App\Models\Evidencia;
use App\Models\Publicacion;
use App\Support\PrivateMediaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use RuntimeException;
use Throwable;

class EvidenciaController extends Controller
{
    /**
     * POST /publicaciones/{id_publicacion}/evidencias
     * Las evidencias se guardan en el disco privado.
     */
    public function store(
        CargarEvidenciasRequest $request,
        int $id_publicacion
    ): JsonResponse {
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
            $publicacion->contratacion
            && $publicacion->contratacion->trabajador
            && (int) $publicacion->contratacion->trabajador->id_usuario
                === (int) $usuario->id_usuario;

        if (! $esPropietario) {
            return $this->error(
                'No tienes permiso para administrar evidencias de esta publicación.',
                'EVIDENCE_ACCESS_DENIED',
                403
            );
        }

        if ((int) $publicacion->contratacion->id_estado_contratacion !== 5) {
            return $this->error(
                'La contratación asociada no está finalizada.',
                'HIRING_NOT_FINISHED',
                409
            );
        }

        $imagenesActuales = $publicacion->evidencias
            ->where('tipo_archivo', 'IMAGEN')
            ->count();

        $videosActuales = $publicacion->evidencias
            ->where('tipo_archivo', 'VIDEO')
            ->count();

        $nuevasImagenes = 0;
        $nuevosVideos = 0;

        foreach ($request->file('archivos') as $archivo) {
            $mime = $archivo->getMimeType();
            $tamano = $archivo->getSize();

            if (str_starts_with($mime, 'image/')) {
                if ($tamano > (10 * 1024 * 1024)) {
                    return $this->error(
                        'Las imágenes no pueden superar los 10 MB.',
                        'IMAGE_TOO_LARGE',
                        422
                    );
                }

                $nuevasImagenes++;
            } else {
                if ($tamano > (100 * 1024 * 1024)) {
                    return $this->error(
                        'Los videos no pueden superar los 100 MB.',
                        'VIDEO_TOO_LARGE',
                        422
                    );
                }

                $nuevosVideos++;
            }
        }

        if (($imagenesActuales + $nuevasImagenes) > 10) {
            return $this->error(
                'Se excedió el límite de 10 imágenes por publicación.',
                'IMAGE_LIMIT_EXCEEDED',
                409
            );
        }

        if (($videosActuales + $nuevosVideos) > 3) {
            return $this->error(
                'Se excedió el límite de 3 videos por publicación.',
                'VIDEO_LIMIT_EXCEEDED',
                409
            );
        }

        $rutasAlmacenadas = [];

        try {
            $evidencias = DB::transaction(
                function () use (
                    $request,
                    $publicacion,
                    &$rutasAlmacenadas
                ) {
                    $creadas = collect();

                    foreach ($request->file('archivos') as $archivo) {
                        $tipoArchivo = str_starts_with(
                            $archivo->getMimeType(),
                            'image/'
                        ) ? 'IMAGEN' : 'VIDEO';

                        $ruta = $archivo->store(
                            'publicaciones',
                            'local'
                        );

                        if (! is_string($ruta) || $ruta === '') {
                            throw new RuntimeException(
                                'No fue posible almacenar una evidencia.'
                            );
                        }

                        $rutasAlmacenadas[] = $ruta;

                        $evidencia = Evidencia::create([
                            'id_publicacion' => $publicacion->id_publicacion,
                            'tipo_archivo' => $tipoArchivo,
                            'ruta_archivo' => $ruta,
                            'fecha_carga' => now(),
                        ]);

                        $creadas->push($evidencia);
                    }

                    return $creadas;
                }
            );
        } catch (Throwable $exception) {
            // Si falla la transacción, eliminar todos los archivos ya almacenados.
            if ($rutasAlmacenadas !== []) {
                Storage::disk('local')->delete($rutasAlmacenadas);
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Evidencias cargadas correctamente.',
            'data' => EvidenciaResource::collection($evidencias),
        ], 201);
    }

    /**
     * GET /evidencias/{id_evidencia}/archivo
     *
     * Los archivos de publicaciones visibles son públicos.
     * Los archivos de publicaciones ocultas solo pueden abrirlos
     * el trabajador propietario o un administrador autenticado.
     */
    public function archivo(
        Request $request,
        int $id_evidencia
    ): JsonResponse|StreamedResponse {
        $evidencia = Evidencia::query()
            ->with([
                'publicacion.contratacion.trabajador',
            ])
            ->find($id_evidencia);

        if (! $evidencia || ! $evidencia->publicacion) {
            return $this->error(
                'Evidencia no encontrada.',
                'EVIDENCE_NOT_FOUND',
                404
            );
        }

        // Autenticación opcional: permite leer medios públicos sin sesión,
        // pero reconoce Bearer válido cuando se consulta un medio oculto.
        $usuario = $request->user('sanctum');

        if ($usuario && ! $usuario->activo) {
            return $this->error(
                'La cuenta está inactiva.',
                'ACCOUNT_INACTIVE',
                403
            );
        }

        $publicacion = $evidencia->publicacion;

        $esAdministrador = $usuario
            && (bool) $usuario->es_administrador;

        $esPropietario =
            $usuario
            && $publicacion->contratacion
            && $publicacion->contratacion->trabajador
            && (int) $publicacion->contratacion->trabajador->id_usuario
                === (int) $usuario->id_usuario;

        if (! $publicacion->visible && ! $esAdministrador && ! $esPropietario) {
            return $this->error(
                'La publicación no está disponible.',
                'PUBLICATION_HIDDEN',
                404
            );
        }

        $ruta = $evidencia->ruta_archivo;

        if (! $ruta || ! Storage::disk('local')->exists($ruta)) {
            return $this->error(
                'El archivo de la evidencia no está disponible.',
                'EVIDENCE_FILE_NOT_FOUND',
                404
            );
        }

        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $nombre = 'evidencia-' . $evidencia->id_evidencia
            . ($extension !== '' ? '.' . $extension : '');

        return PrivateMediaResponse::inline(
            'local',
            $ruta,
            $nombre
        );
    }

    /**
     * DELETE /evidencias/{id_evidencia}
     */
    public function destroy(
        Request $request,
        int $id_evidencia
    ): Response {
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

        $esAdministrador = (bool) $usuario->es_administrador;

        $esPropietario =
            $evidencia->publicacion
            && $evidencia->publicacion->contratacion
            && $evidencia->publicacion->contratacion->trabajador
            && (int) $evidencia->publicacion->contratacion->trabajador->id_usuario
                === (int) $usuario->id_usuario;

        if (! $esAdministrador && ! $esPropietario) {
            return $this->error(
                'No tienes permiso para eliminar esta evidencia.',
                'EVIDENCE_ACCESS_DENIED',
                403
            );
        }

        $ruta = $evidencia->ruta_archivo;

        DB::transaction(function () use ($evidencia): void {
            $evidencia->delete();
        });

        if ($ruta) {
            Storage::disk('local')->delete($ruta);
            // Retirar también una copia heredada si aún no se ejecutó la migración BE-16.
            Storage::disk('public')->delete($ruta);
        }

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
