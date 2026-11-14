<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificacionResource;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificacionController extends Controller
{
    /**
     * GET /api/v1/notificaciones
     * Devuelve exclusivamente las notificaciones del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'leida' => [
                'sometimes',
                'string',
                Rule::in(['true', 'false', '1', '0']),
            ],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);

        $consulta = Notificacion::query()
            ->deUsuario((int) $request->user()->id_usuario)
            ->with('tipoNotificacion');

        if (array_key_exists('leida', $filtros)) {
            $leida = in_array(
                strtolower($filtros['leida']),
                ['true', '1'],
                true
            );

            $consulta->where('leida', $leida);
        }

        $paginador = $consulta
            ->orderByDesc('fecha_creacion')
            ->orderByDesc('id_notificacion')
            ->paginate((int) ($filtros['per_page'] ?? 15))
            ->appends($filtros);

        return response()->json([
            'success' => true,
            'message' => 'Notificaciones obtenidas correctamente.',
            'data' => NotificacionResource::collection(
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
     * GET /api/v1/notificaciones/no-leidas/count
     */
    public function countNoLeidas(Request $request): JsonResponse
    {
        $total = Notificacion::query()
            ->deUsuario((int) $request->user()->id_usuario)
            ->noLeidas()
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Conteo obtenido correctamente.',
            'data' => [
                'total' => $total,
            ],
        ]);
    }

    /**
     * PATCH /api/v1/notificaciones/{id_notificacion}/leer
     */
    public function marcarComoLeida(
        Request $request,
        int $id_notificacion
    ): JsonResponse {
        $notificacion = Notificacion::query()
            ->deUsuario((int) $request->user()->id_usuario)
            ->with('tipoNotificacion')
            ->find($id_notificacion);

        if (! $notificacion) {
            return $this->error(
                'Notificación no encontrada.',
                'RESOURCE_NOT_FOUND',
                404
            );
        }

        if (! $notificacion->leida) {
            $notificacion->leida = true;
            $notificacion->save();
        }

        $notificacion->refresh()->load('tipoNotificacion');

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída.',
            'data' => NotificacionResource::make($notificacion)
                ->resolve($request),
        ]);
    }

    /**
     * PATCH /api/v1/notificaciones/leer-todas
     */
    public function marcarTodasComoLeidas(Request $request): JsonResponse
    {
        $actualizadas = Notificacion::query()
            ->deUsuario((int) $request->user()->id_usuario)
            ->noLeidas()
            ->update(['leida' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones fueron marcadas como leídas.',
            'data' => [
                'actualizadas' => $actualizadas,
            ],
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
