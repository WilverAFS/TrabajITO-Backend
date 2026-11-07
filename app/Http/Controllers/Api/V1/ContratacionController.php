<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CrearContratacionRequest;
use App\Http\Resources\Api\V1\ContratacionResource;
use App\Models\Contratacion;
use App\Models\PerfilProfesional;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContratacionController extends Controller
{
    /**
     * POST /api/v1/contrataciones
     *
     * Solicitar la contratación de un trabajador.
     */
    public function store(
        CrearContratacionRequest $request
    ): JsonResponse {
        $datos = $request->validated();
        $usuario = $request->user();

        // Comprobar que el perfil profesional existe.
        $trabajador = PerfilProfesional::query()
            ->with('usuario')
            ->find($datos['id_trabajador']);

        if (!$trabajador) {
            return $this->error(
                'Trabajador no encontrado.',
                'WORKER_NOT_FOUND',
                404
            );
        }

        // El perfil y su cuenta deben estar activos.
        if (
            !$trabajador->activo ||
            !$trabajador->usuario ||
            !$trabajador->usuario->activo
        ) {
            return $this->error(
                'El trabajador seleccionado no está habilitado.',
                'WORKER_NOT_AVAILABLE',
                422,
                [
                    'id_trabajador' => [
                        'El trabajador debe tener un perfil y una cuenta activos.',
                    ],
                ]
            );
        }

        // Un usuario no puede contratar su propio perfil.
        if (
            (int) $trabajador->id_usuario ===
            (int) $usuario->id_usuario
        ) {
            return $this->error(
                'No puedes contratar tu propio perfil profesional.',
                'SELF_HIRING_NOT_ALLOWED',
                409
            );
        }

        // El cliente se obtiene del usuario autenticado.
        // Nunca se acepta id_cliente desde el formulario.
        $contratacion = $usuario
            ->contratacionesSolicitadas()
            ->create([
                'id_trabajador' => $trabajador->id_trabajador,
                'id_estado_contratacion' => 1,
                'descripcion_servicio' =>
                    $datos['descripcion_servicio'],
            ]);

        // Recuperar la fecha generada por PostgreSQL.
        $contratacion->refresh();

        $contratacion->load([
            'cliente',
            'trabajador.usuario',
            'estado',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Solicitud de contratación creada correctamente.',
            'data' => (
                new ContratacionResource($contratacion)
            )->resolve($request),
        ], 201);
    }

    /**
     * GET /api/v1/contrataciones
     *
     * Consultar las contrataciones propias del usuario.
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'participacion' => [
                'sometimes',
                Rule::in(['cliente', 'trabajador']),
            ],
            'id_estado' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'between:1,50',
            ],
        ]);

        $idUsuario = $request->user()->id_usuario;

        $consulta = Contratacion::query()->with([
    'cliente',
    'trabajador.usuario',
    'estado',
    'publicacion',
]);

        // Solo contrataciones en las que participa el usuario.
        $participacion = $filtros['participacion'] ?? null;

        if ($participacion === 'cliente') {
            $consulta->where('id_cliente', $idUsuario);
        } elseif ($participacion === 'trabajador') {
            $consulta->whereHas(
                'trabajador',
                function (Builder $query) use ($idUsuario) {
                    $query->where('id_usuario', $idUsuario);
                }
            );
        } else {
            // Cuando no hay filtro, incluir ambos roles.
            $consulta->where(
                function (Builder $query) use ($idUsuario) {
                    $query
                        ->where('id_cliente', $idUsuario)
                        ->orWhereHas(
                            'trabajador',
                            function (Builder $perfil) use ($idUsuario) {
                                $perfil->where(
                                    'id_usuario',
                                    $idUsuario
                                );
                            }
                        );
                }
            );
        }

        // Filtrar por estado de contratación.
        if (isset($filtros['id_estado'])) {
            $consulta->where(
                'id_estado_contratacion',
                $filtros['id_estado']
            );
        }

        // Historial ordenado del más reciente al más antiguo.
        $consulta
            ->orderByDesc('fecha_solicitud')
            ->orderByDesc('id_contratacion');

        $perPage = (int) ($filtros['per_page'] ?? 15);

        $paginador = $consulta
            ->paginate($perPage)
            ->appends($filtros);

        return response()->json([
            'success' => true,
            'message' =>
                'Contrataciones obtenidas correctamente.',

            'data' => ContratacionResource::collection(
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
                'last' => $paginador->url(
                    $paginador->lastPage()
                ),
                'prev' => $paginador->previousPageUrl(),
                'next' => $paginador->nextPageUrl(),
            ],
        ]);
    }

    /**
     * GET /api/v1/contrataciones/{id_contratacion}
     *
     * Consultar una contratación específica.
     */
    public function show(
        Request $request,
        int $id_contratacion
    ): JsonResponse {
        $contratacion = Contratacion::query()
            ->with([
                'cliente',
                'trabajador.usuario',
                'trabajador.oficios',
                'estado',
                'publicacion',
            ])
            ->find($id_contratacion);

        if (!$contratacion) {
            return $this->error(
                'Contratación no encontrada.',
                'HIRING_NOT_FOUND',
                404
            );
        }

        $usuario = $request->user();
        $idUsuario = (int) $usuario->id_usuario;

        $esCliente = (
            (int) $contratacion->id_cliente === $idUsuario
        );

        $esTrabajador = (
            (int) ($contratacion->trabajador?->id_usuario ?? 0)
            === $idUsuario
        );

        $esAdministrador = (bool) $usuario->es_administrador;

        // Impedir consultar contrataciones ajenas.
        if (
            !$esCliente &&
            !$esTrabajador &&
            !$esAdministrador
        ) {
            return $this->error(
                'No tienes permiso para consultar esta contratación.',
                'HIRING_ACCESS_DENIED',
                403
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Contratación obtenida correctamente.',
            'data' => (
                new ContratacionResource($contratacion)
            )->resolve($request),
        ]);
    }

    /**
     * Estructura común para errores de la API.
     */
    private function error(
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
}
