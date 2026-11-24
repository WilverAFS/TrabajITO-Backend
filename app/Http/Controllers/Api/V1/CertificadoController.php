<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCertificadoRequest;
use App\Http\Resources\Api\V1\CertificadoResource;
use App\Models\Certificado;
use App\Support\PrivateMediaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CertificadoController extends Controller
{
    // Respuesta para errores.
    private function respuestaError(
        string $mensaje,
        string $codigo,
        int $estado,
        ?array $errores = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
            'error_code' => $codigo,
            'errors' => $errores,
        ], $estado);
    }

    // Consultar los certificados del trabajador autenticado.
    public function index(Request $request): JsonResponse
    {
        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (! $perfil) {
            return $this->respuestaError(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        $certificados = $perfil->certificados()
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id_certificado')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Certificados obtenidos correctamente.',
            'data' => CertificadoResource::collection(
                $certificados
            )->resolve($request),
        ], 200);
    }

    // Registrar un certificado y almacenar su documento en el disco privado.
    public function store(
        StoreCertificadoRequest $request
    ): JsonResponse {
        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (! $perfil) {
            return $this->respuestaError(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        $datos = $request->validated();
        $archivo = $datos['documento'];

        $rutaDocumento = $archivo->store(
            'certificados',
            'local'
        );

        if (! $rutaDocumento) {
            return $this->respuestaError(
                'No fue posible almacenar el documento.',
                'DOCUMENT_UPLOAD_FAILED',
                500
            );
        }

        try {
            $certificado = $perfil->certificados()->create([
                'nombre' => $datos['nombre'],
                'institucion_emisora' => $datos['institucion_emisora'],
                'fecha_emision' => $datos['fecha_emision'],
                'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
                'ruta_documento' => $rutaDocumento,
            ]);
        } catch (Throwable $exception) {
            // Evitar archivos huérfanos si falla el registro en la base de datos.
            Storage::disk('local')->delete($rutaDocumento);

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Certificado registrado correctamente.',
            'data' => new CertificadoResource($certificado),
        ], 201);
    }

    /**
     * Abrir un certificado únicamente para su titular o para administración.
     * GET /api/v1/certificados/{id_certificado}/documento
     */
    public function documento(
        Request $request,
        int $id_certificado
    ): JsonResponse|StreamedResponse {
        $certificado = Certificado::query()
            ->with('perfilProfesional')
            ->find($id_certificado);

        if (! $certificado) {
            return $this->respuestaError(
                'El certificado solicitado no existe.',
                'CERTIFICATE_NOT_FOUND',
                404
            );
        }

        $usuario = $request->user();

        $esPropietario = $certificado->perfilProfesional
            && (int) $certificado->perfilProfesional->id_usuario
                === (int) $usuario->id_usuario;

        $esAdministrador = (bool) $usuario->es_administrador;

        if (! $esPropietario && ! $esAdministrador) {
            return $this->respuestaError(
                'No tienes permiso para consultar este certificado.',
                'CERTIFICATE_ACCESS_DENIED',
                403
            );
        }

        $ruta = $certificado->ruta_documento;

        if (! $ruta || ! Storage::disk('local')->exists($ruta)) {
            return $this->respuestaError(
                'El documento del certificado no está disponible.',
                'CERTIFICATE_FILE_NOT_FOUND',
                404
            );
        }

        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $nombre = 'certificado-' . $certificado->id_certificado
            . ($extension !== '' ? '.' . $extension : '');

        return PrivateMediaResponse::inline(
            'local',
            $ruta,
            $nombre
        );
    }

    // Eliminar un certificado propio y su documento privado.
    public function destroy(
        Request $request,
        int $id_certificado
    ): Response {
        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (! $perfil) {
            return $this->respuestaError(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        // Buscar exclusivamente dentro del perfil autenticado.
        $certificado = $perfil->certificados()
            ->whereKey($id_certificado)
            ->first();

        if (! $certificado) {
            return $this->respuestaError(
                'El certificado solicitado no existe.',
                'CERTIFICATE_NOT_FOUND',
                404
            );
        }

        $rutaDocumento = $certificado->ruta_documento;

        $certificado->delete();

        if ($rutaDocumento) {
            Storage::disk('local')->delete($rutaDocumento);
            // Retirar también una copia heredada si aún no se ejecutó la migración BE-16.
            Storage::disk('public')->delete($rutaDocumento);
        }

        return response()->noContent();
    }
}
