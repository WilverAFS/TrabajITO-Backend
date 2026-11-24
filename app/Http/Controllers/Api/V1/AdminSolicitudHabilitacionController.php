<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AdminSolicitudHabilitacionResource;
use App\Http\Resources\Api\V1\CertificadoResource;
use App\Models\PerfilProfesional;
use App\Models\SolicitudHabilitacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminSolicitudHabilitacionController extends Controller
{
    private const RELACIONES = [
        'estadoSolicitud',
        'administradorRevisor',
        'perfilProfesional.usuario',
        'perfilProfesional.municipio',
        'perfilProfesional.oficios',
        'perfilProfesional.certificados',
    ];

    // GET /api/v1/admin/solicitudes-habilitacion
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'id_estado' => [
                'sometimes',
                'integer',
                'min:1',
                'exists:estados_solicitud,id_estado_solicitud',
            ],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
            'sort' => [
                'sometimes',
                Rule::in(['fecha_solicitud']),
            ],
            'direction' => [
                'sometimes',
                Rule::in(['asc', 'desc']),
            ],
        ]);

        $consulta = SolicitudHabilitacion::query()
            ->with(self::RELACIONES);

        if (isset($filtros['id_estado'])) {
            $consulta->where(
                'id_estado_solicitud',
                (int) $filtros['id_estado']
            );
        }

        $direccion = $filtros['direction'] ?? 'desc';

        $paginador = $consulta
            ->orderBy('fecha_solicitud', $direccion)
            ->orderBy('id_solicitud', $direccion)
            ->paginate((int) ($filtros['per_page'] ?? 15))
            ->appends($filtros);

        return response()->json([
            'success' => true,
            'message' => 'Solicitudes de habilitación obtenidas correctamente.',
            'data' => AdminSolicitudHabilitacionResource::collection(
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

    // GET /api/v1/admin/solicitudes-habilitacion/{id_solicitud}
    public function show(
        Request $request,
        int $id_solicitud
    ): JsonResponse {
        $solicitud = SolicitudHabilitacion::query()
            ->with(self::RELACIONES)
            ->find($id_solicitud);

        if (! $solicitud) {
            return $this->error(
                'Solicitud de habilitación no encontrada.',
                'RESOURCE_NOT_FOUND',
                404
            );
        }

        $datos = (new AdminSolicitudHabilitacionResource($solicitud))
            ->resolve($request);

        if ($solicitud->perfilProfesional) {
            $datos['perfil']['certificados'] = CertificadoResource::collection(
                $solicitud->perfilProfesional->certificados
            )->resolve($request);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de habilitación obtenida correctamente.',
            'data' => $datos,
        ]);
    }

    // PATCH /api/v1/admin/solicitudes-habilitacion/{id}/revisar
    public function revisar(
        Request $request,
        int $id_solicitud
    ): JsonResponse {
        return $this->transicionar(
            $request,
            $id_solicitud,
            2,
            'Solicitud marcada en revisión.'
        );
    }

    // PATCH /api/v1/admin/solicitudes-habilitacion/{id}/aprobar
    public function aprobar(
        Request $request,
        int $id_solicitud
    ): JsonResponse {
        $comentario = $request->input('comentario_revision');
        if (is_string($comentario)) {
            $request->merge(['comentario_revision' => trim($comentario)]);
        }

        $datos = $request->validate([
            'comentario_revision' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->transicionar(
            $request,
            $id_solicitud,
            3,
            'Solicitud aprobada correctamente.',
            $datos['comentario_revision'] ?? null
        );
    }

    // PATCH /api/v1/admin/solicitudes-habilitacion/{id}/rechazar
    public function rechazar(
        Request $request,
        int $id_solicitud
    ): JsonResponse {
        $comentario = $request->input('comentario_revision');
        if (is_string($comentario)) {
            $request->merge(['comentario_revision' => trim($comentario)]);
        }

        $datos = $request->validate([
            'comentario_revision' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        return $this->transicionar(
            $request,
            $id_solicitud,
            4,
            'Solicitud rechazada correctamente.',
            $datos['comentario_revision']
        );
    }

    private function transicionar(
        Request $request,
        int $idSolicitud,
        int $nuevoEstado,
        string $mensaje,
        ?string $comentario = null
    ): JsonResponse {
        $resultado = DB::transaction(function () use (
            $request,
            $idSolicitud,
            $nuevoEstado,
            $comentario
        ) {
            $solicitud = SolicitudHabilitacion::query()
                ->whereKey($idSolicitud)
                ->lockForUpdate()
                ->first();

            if (! $solicitud) {
                return $this->error(
                    'Solicitud de habilitación no encontrada.',
                    'RESOURCE_NOT_FOUND',
                    404
                );
            }

            $estadoActual = (int) $solicitud->id_estado_solicitud;

            // Revisar: únicamente Pendiente -> En revisión.
            if ($nuevoEstado === 2 && $estadoActual !== 1) {
                return $this->error(
                    'La solicitud no está pendiente de revisión.',
                    'OPERATION_FAILED',
                    409
                );
            }

            // Aprobar/rechazar: no modificar solicitudes concluidas.
            if (
                in_array($nuevoEstado, [3, 4], true) &&
                ! in_array($estadoActual, [1, 2], true)
            ) {
                return $this->error(
                    'La solicitud ya fue resuelta.',
                    'OPERATION_FAILED',
                    409
                );
            }

            $perfil = null;

            if ($nuevoEstado === 3) {
                $perfil = PerfilProfesional::query()
                    ->whereKey($solicitud->id_trabajador)
                    ->lockForUpdate()
                    ->first();

                if (! $perfil) {
                    return $this->error(
                        'Perfil profesional no encontrado.',
                        'RESOURCE_NOT_FOUND',
                        404
                    );
                }

                if ($perfil->activo) {
                    return $this->error(
                        'El perfil profesional ya está habilitado.',
                        'OPERATION_FAILED',
                        409
                    );
                }
            }

            $solicitud->id_estado_solicitud = $nuevoEstado;
            $solicitud->id_admin_revisor =
                (int) $request->user()->id_usuario;
            $solicitud->fecha_revision = now();

            if ($nuevoEstado !== 2) {
                $solicitud->comentario_revision = $comentario;
            }

            $solicitud->save();

            if ($nuevoEstado === 3) {
                $perfil->activo = true;
                $perfil->save();
            }

            return $solicitud->load(self::RELACIONES);
        });

        if ($resultado instanceof JsonResponse) {
            return $resultado;
        }

        $datos = (new AdminSolicitudHabilitacionResource($resultado))
            ->resolve($request);

        if ($nuevoEstado === 3) {
            $datos['perfil_activo'] =
                (bool) $resultado->perfilProfesional->activo;
        }

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $datos,
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
