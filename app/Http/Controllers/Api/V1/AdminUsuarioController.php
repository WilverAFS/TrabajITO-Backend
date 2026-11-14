<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminUsuarioController extends Controller
{
    /**
     * GET /api/v1/admin/usuarios
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'buscar' => ['sometimes', 'string', 'max:255'],
            'activo' => [
                'sometimes',
                'string',
                Rule::in(['true', 'false', '1', '0']),
            ],
            'es_administrador' => [
                'sometimes',
                'string',
                Rule::in(['true', 'false', '1', '0']),
            ],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);

        $consulta = User::query()->with([
            'perfilProfesional.solicitudesHabilitacion.estadoSolicitud',
        ]);

        if (! empty($filtros['buscar'])) {
            $patron = '%' . strtolower(trim($filtros['buscar'])) . '%';

            $consulta->where(function ($query) use ($patron) {
                $query
                    ->whereRaw('LOWER(nombre) LIKE ?', [$patron])
                    ->orWhereRaw('LOWER(apellido) LIKE ?', [$patron])
                    ->orWhereRaw('LOWER(correo) LIKE ?', [$patron])
                    ->orWhereRaw('LOWER(telefono) LIKE ?', [$patron]);
            });
        }

        if (array_key_exists('activo', $filtros)) {
            $consulta->where(
                'activo',
                in_array(strtolower($filtros['activo']), ['true', '1'], true)
            );
        }

        if (array_key_exists('es_administrador', $filtros)) {
            $consulta->where(
                'es_administrador',
                in_array(
                    strtolower($filtros['es_administrador']),
                    ['true', '1'],
                    true
                )
            );
        }

        $paginador = $consulta
            ->orderByDesc('fecha_registro')
            ->orderByDesc('id_usuario')
            ->paginate((int) ($filtros['per_page'] ?? 15))
            ->appends($filtros);

        return response()->json([
            'success' => true,
            'message' => 'Usuarios obtenidos correctamente.',
            'data' => UsuarioResource::collection(
                $paginador->getCollection()
            )->resolve($request),
            'meta' => [
                'current_page' => $paginador->currentPage(),
                'per_page' => $paginador->perPage(),
                'last_page' => $paginador->lastPage(),
                'total' => $paginador->total(),
            ],
            'links' => [
                'first' => $paginador->url(1),
                'last' => $paginador->url($paginador->lastPage()),
                'prev' => $paginador->previousPageUrl(),
                'next' => $paginador->nextPageUrl(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/usuarios/{id_usuario}
     */
    public function show(
        Request $request,
        int $id_usuario
    ): JsonResponse {
        $usuario = User::query()
            ->with([
                'perfilProfesional.solicitudesHabilitacion.estadoSolicitud',
            ])
            ->find($id_usuario);

        if (! $usuario) {
            return $this->error(
                'Usuario no encontrado.',
                'RESOURCE_NOT_FOUND',
                404
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario obtenido correctamente.',
            'data' => (new UsuarioResource($usuario, true))
                ->resolve($request),
        ]);
    }

    /**
     * PATCH /api/v1/admin/usuarios/{id_usuario}/suspender
     * La suspensión de la cuenta también suspende su perfil profesional.
     */
    public function suspender(
        Request $request,
        int $id_usuario
    ): JsonResponse {
        $usuario = User::query()
            ->with('perfilProfesional')
            ->find($id_usuario);

        if (! $usuario) {
            return $this->error(
                'Usuario no encontrado.',
                'RESOURCE_NOT_FOUND',
                404
            );
        }

        DB::transaction(function () use ($usuario): void {
            $usuario->activo = false;
            $usuario->save();

            if ($usuario->perfilProfesional) {
                $usuario->perfilProfesional->activo = false;
                $usuario->perfilProfesional->save();
            }
        });

        $usuario->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Usuario suspendido correctamente.',
            'data' => (new UsuarioResource($usuario, false, true))
                ->resolve($request),
        ]);
    }

    /**
     * PATCH /api/v1/admin/usuarios/{id_usuario}/reactivar
     * La reactivación de la cuenta NO reactiva el perfil profesional.
     */
    public function reactivar(
        Request $request,
        int $id_usuario
    ): JsonResponse {
        $usuario = User::query()->find($id_usuario);

        if (! $usuario) {
            return $this->error(
                'Usuario no encontrado.',
                'RESOURCE_NOT_FOUND',
                404
            );
        }

        $usuario->activo = true;
        $usuario->save();
        $usuario->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Usuario reactivado correctamente.',
            'data' => (new UsuarioResource($usuario, false, true))
                ->resolve($request),
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
            'errors' => null,
        ], $estado);
    }
}
