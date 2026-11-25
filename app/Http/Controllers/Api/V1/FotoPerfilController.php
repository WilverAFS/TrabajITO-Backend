<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFotoPerfilRequest;
use App\Http\Resources\Api\V1\UsuarioCuentaResource;
use App\Models\User;
use App\Support\PrivateMediaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class FotoPerfilController extends Controller
{
    /**
     * POST /api/v1/me/foto-perfil
     * Carga o reemplaza la fotografía de la cuenta autenticada.
     */
    public function store(StoreFotoPerfilRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $archivo = $request->file('foto');

        $rutaNueva = $archivo->store('fotos-perfil', 'local');

        if (! is_string($rutaNueva) || $rutaNueva === '') {
            return $this->error(
                'No fue posible almacenar la fotografía.',
                'PHOTO_UPLOAD_FAILED',
                500
            );
        }

        $rutaAnterior = $usuario->foto_perfil;

        try {
            $usuario->foto_perfil = $rutaNueva;
            $usuario->save();
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($rutaNueva);

            throw $exception;
        }

        // El archivo anterior se elimina únicamente después de guardar la nueva ruta.
        if ($rutaAnterior && $rutaAnterior !== $rutaNueva) {
            Storage::disk('local')->delete($rutaAnterior);
        }

        $usuario->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Fotografía de cuenta actualizada correctamente.',
            'data' => UsuarioCuentaResource::make($usuario)->resolve($request),
        ], 201);
    }

    /**
     * DELETE /api/v1/me/foto-perfil
     * Elimina la fotografía de la cuenta autenticada.
     */
    public function destroy(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $rutaAnterior = $usuario->foto_perfil;

        $usuario->foto_perfil = null;
        $usuario->save();

        if ($rutaAnterior) {
            Storage::disk('local')->delete($rutaAnterior);
        }

        $usuario->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Fotografía de cuenta eliminada correctamente.',
            'data' => UsuarioCuentaResource::make($usuario)->resolve($request),
        ], 200);
    }

    /**
     * GET /api/v1/usuarios/{id_usuario}/foto
     *
     * La fotografía es pública únicamente para trabajadores con cuenta y
     * perfil profesional activos. Las fotografías de clientes y
     * administradores requieren ser titular o administrador autenticado.
     */
    public function show(
        Request $request,
        int $id_usuario
    ): JsonResponse|StreamedResponse {
        $usuario = User::query()
            ->with('perfilProfesional')
            ->find($id_usuario);

        if (! $usuario) {
            return $this->error(
                'El usuario solicitado no existe.',
                'USER_NOT_FOUND',
                404
            );
        }

        // Se permite autenticación opcional para conservar el acceso público
        // a fotografías de trabajadores activos.
        $autenticado = $request->user('sanctum');

        if ($autenticado && ! $autenticado->activo) {
            return $this->error(
                'La cuenta autenticada está inactiva.',
                'ACCOUNT_INACTIVE',
                403
            );
        }

        $esAdministrador = $autenticado
            && (bool) $autenticado->es_administrador;

        if (! $usuario->activo && ! $esAdministrador) {
            return $this->error(
                'La fotografía solicitada no está disponible.',
                'PHOTO_NOT_AVAILABLE',
                $autenticado ? 403 : 404
            );
        }

        $esPropietario = $autenticado
            && (int) $autenticado->id_usuario === (int) $usuario->id_usuario;

        $esTrabajadorPublico = (bool) $usuario->activo
            && ! (bool) $usuario->es_administrador
            && $usuario->perfilProfesional !== null
            && (bool) $usuario->perfilProfesional->activo;

        if (! $esPropietario && ! $esAdministrador && ! $esTrabajadorPublico) {
            return $this->error(
                'No tienes permiso para consultar esta fotografía.',
                'PHOTO_ACCESS_DENIED',
                $autenticado ? 403 : 404
            );
        }

        $ruta = $usuario->foto_perfil;

        if (! $ruta || ! Storage::disk('local')->exists($ruta)) {
            return $this->error(
                'El usuario no tiene una fotografía disponible.',
                'PHOTO_NOT_FOUND',
                404
            );
        }

        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $nombre = 'foto-perfil-' . $usuario->id_usuario
            . ($extension !== '' ? '.' . $extension : '');

        return PrivateMediaResponse::inline(
            'local',
            $ruta,
            $nombre
        );
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
            'errors' => null,
        ], $estado);
    }
}
