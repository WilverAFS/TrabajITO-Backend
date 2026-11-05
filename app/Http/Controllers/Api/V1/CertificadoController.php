<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCertificadoRequest;
use App\Http\Resources\Api\V1\CertificadoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CertificadoController extends Controller
{
    // Respuesta para errores
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

    // Consultar los certificados del trabajador autenticado
    public function index(Request $request): JsonResponse
    {
        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (!$perfil) {
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

    // Registrar un certificado y almacenar su documento
    public function store(
        StoreCertificadoRequest $request
    ): JsonResponse {

        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (!$perfil) {
            return $this->respuestaError(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        $datos = $request->validated();

        $archivo = $datos['documento'];

        // Almacenar el documento en el disco publico
        $rutaDocumento = $archivo->store(
            'certificados',
            'public'
        );

        if (!$rutaDocumento) {
            return $this->respuestaError(
                'No fue posible almacenar el documento.',
                'DOCUMENT_UPLOAD_FAILED',
                500
            );
        }

        try {

            // Crear el certificado asociado al perfil autenticado
            $certificado = $perfil->certificados()->create([
                'nombre' => $datos['nombre'],
                'institucion_emisora' => $datos['institucion_emisora'],
                'fecha_emision' => $datos['fecha_emision'],
                'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
                'ruta_documento' => $rutaDocumento,
            ]);

        } catch (Throwable $exception) {

            // Eliminar el archivo si no se pudo registrar en la BD
            Storage::disk('public')->delete($rutaDocumento);

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Certificado registrado correctamente.',
            'data' => new CertificadoResource($certificado),
        ], 201);
    }

    // Eliminar un certificado propio y su documento
    public function destroy(
        Request $request,
        int $id_certificado
    ): Response {

        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (!$perfil) {
            return $this->respuestaError(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        // Buscar exclusivamente dentro del perfil autenticado
        $certificado = $perfil->certificados()
            ->whereKey($id_certificado)
            ->first();

        if (!$certificado) {
            return $this->respuestaError(
                'El certificado solicitado no existe.',
                'CERTIFICATE_NOT_FOUND',
                404
            );
        }

        $rutaDocumento = $certificado->ruta_documento;

        // Eliminar primero el registro de la base de datos
        $certificado->delete();

        // Eliminar el archivo almacenado
        if ($rutaDocumento) {
            Storage::disk('public')->delete($rutaDocumento);
        }

        // El contrato establece 204 sin cuerpo JSON
        return response()->noContent();
    }
}
