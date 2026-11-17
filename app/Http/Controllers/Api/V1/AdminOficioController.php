<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BuscarOficiosRequest;
use App\Http\Requests\Api\V1\StoreOficioRequest;
use App\Http\Requests\Api\V1\UpdateOficioRequest;
use App\Http\Resources\Api\V1\AdminOficioResource;
use App\Models\Oficio;
use Illuminate\Http\JsonResponse;

class AdminOficioController extends Controller
{
    /**
     * GET /api/v1/admin/oficios
     */
    public function index(BuscarOficiosRequest $request): JsonResponse
    {
        $filtros = $request->validated();
        $buscar = $filtros['buscar'] ?? null;

        $query = Oficio::query();

        if ($buscar !== null && $buscar !== '') {
            $query->whereRaw(
                'LOWER(nombre) LIKE ?',
                ['%' . mb_strtolower($buscar, 'UTF-8') . '%']
            );
        }

        $oficios = $query
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Oficios obtenidos correctamente.',
            'data' => AdminOficioResource::collection($oficios),
        ]);
    }

    /**
     * POST /api/v1/admin/oficios
     */
    public function store(StoreOficioRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $oficio = Oficio::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'activo' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Oficio creado correctamente.',
            'data' => new AdminOficioResource($oficio),
        ], 201);
    }

    /**
     * PATCH /api/v1/admin/oficios/{id_oficio}
     */
    public function update(
        UpdateOficioRequest $request,
        int $id_oficio
    ): JsonResponse {
        $oficio = Oficio::find($id_oficio);

        if (! $oficio) {
            return $this->error(
                'Oficio no encontrado.',
                'RESOURCE_NOT_FOUND',
                404
            );
        }

        $oficio->fill($request->validated());
        $oficio->save();

        return response()->json([
            'success' => true,
            'message' => 'Oficio actualizado correctamente.',
            'data' => new AdminOficioResource($oficio),
        ]);
    }

    /**
     * PATCH /api/v1/admin/oficios/{id_oficio}/desactivar
     */
    public function desactivar(int $id_oficio): JsonResponse
    {
        $oficio = Oficio::find($id_oficio);

        if (! $oficio) {
            return $this->error(
                'Oficio no encontrado.',
                'RESOURCE_NOT_FOUND',
                404
            );
        }

        $oficio->activo = false;
        $oficio->save();

        return response()->json([
            'success' => true,
            'message' => 'Oficio desactivado correctamente.',
            'data' => [
                'id_oficio' => $oficio->id_oficio,
                'activo' => false,
            ],
        ]);
    }

    /**
     * Respuesta de error estándar.
     */
    private function error(
        string $message,
        string $errorCode,
        int $status
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'errors' => null,
        ], $status);
    }
}
