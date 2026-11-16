<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ResolverReporteRequest;
use App\Http\Resources\Api\V1\AdminReporteResource;
use App\Models\PerfilProfesional;
use App\Models\Publicacion;
use App\Models\Reporte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminReporteController extends Controller
{
    private const RELACIONES = [
        'estado',
        'denunciante',
        'moderador',
    ];

    // GET /api/v1/admin/reportes
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'id_estado' => [
                'sometimes',
                'integer',
            ],

            'tipo_objetivo' => [
                'sometimes',
                Rule::in([
                    'PUBLICACION',
                    'PERFIL',
                ]),
            ],

            'buscar' => [
                'sometimes',
                'string',
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

            'sort' => [
                'sometimes',
                Rule::in([
                    'fecha_creacion',
                ]),
            ],

            'direction' => [
                'sometimes',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
        ]);

        $consulta = Reporte::query()
            ->with(self::RELACIONES);

        if (isset($filtros['id_estado'])) {
            $consulta->deEstado(
                (int) $filtros['id_estado']
            );
        }

        if (isset($filtros['tipo_objetivo'])) {
            $consulta->deTipoObjetivo(
                $filtros['tipo_objetivo']
            );
        }

        if (! empty($filtros['buscar'])) {
            $texto = trim($filtros['buscar']);

            $consulta->where(function ($query) use ($texto) {
                $query->where(
                    'motivo',
                    'like',
                    '%'.$texto.'%'
                )->orWhere(
                    'descripcion',
                    'like',
                    '%'.$texto.'%'
                );
            });
        }

        $campoOrden = $filtros['sort']
            ?? 'fecha_creacion';

        $direccion = $filtros['direction']
            ?? 'desc';

        $paginador = $consulta
            ->orderBy(
                $campoOrden,
                $direccion
            )
            ->orderBy(
                'id_reporte',
                $direccion
            )
            ->paginate(
                (int) (
                    $filtros['per_page']
                    ?? 15
                )
            )
            ->appends($filtros);

        return response()->json([
            'success' => true,

            'message' => 'Reportes obtenidos correctamente.',

            'data' => AdminReporteResource::collection(
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

    // GET /api/v1/admin/reportes/{id_reporte}
    public function show(
        Request $request,
        int $id_reporte
    ): JsonResponse {
        $reporte = Reporte::query()
            ->with(self::RELACIONES)
            ->find($id_reporte);

        if (! $reporte) {
            return $this->error(
                'Reporte no encontrado.',
                'RESOURCE_NOT_FOUND',
                404
            );
        }

        $objetivo = null;

        if ($reporte->tipo_objetivo === 'PUBLICACION') {

            $publicacion = Publicacion::query()
                ->select([
                    'id_publicacion',
                    'titulo',
                    'visible',
                ])
                ->find($reporte->id_objetivo);

            if ($publicacion) {
                $objetivo = [
                    'id_publicacion' => $publicacion->id_publicacion,

                    'titulo' => $publicacion->titulo,

                    'visible' => (bool) $publicacion->visible,
                ];
            }
        }

        if ($reporte->tipo_objetivo === 'PERFIL') {

            $perfil = PerfilProfesional::query()
                ->with('usuario')
                ->find($reporte->id_objetivo);

            if (
                $perfil &&
                $perfil->usuario
            ) {
                $objetivo = [
                    'id_trabajador' => $perfil->id_trabajador,

                    'nombre_completo' => trim(
                        $perfil->usuario->nombre.
                        ' '.
                        $perfil->usuario->apellido
                    ),
                ];
            }
        }

        $datos = (
            new AdminReporteResource(
                $reporte
            )
        )->resolve($request);

        $datos['objetivo'] = $objetivo;

        return response()->json([
            'success' => true,
            'message' => 'Reporte obtenido correctamente.',
            'data' => $datos,
        ]);
    }

    // PATCH /api/v1/admin/reportes/{id_reporte}/revisar
    public function revisar(
        Request $request,
        int $id_reporte
    ): JsonResponse {
        return $this->transicionar(
            $request,
            $id_reporte,
            2,
            'Reporte marcado en revisión.'
        );
    }

    // PATCH /api/v1/admin/reportes/{id_reporte}/resolver
    public function resolver(
        ResolverReporteRequest $request,
        int $id_reporte
    ): JsonResponse {
        return $this->transicionar(
            $request,
            $id_reporte,
            3,
            'Reporte resuelto correctamente.',
            $request->validated()['resolucion']
        );
    }

    private function transicionar(
        Request $request,
        int $idReporte,
        int $nuevoEstado,
        string $mensaje,
        ?string $resolucion = null
    ): JsonResponse {

        $reporte = Reporte::query()
            ->with(self::RELACIONES)
            ->find($idReporte);

        if (! $reporte) {
            return $this->error(
                'Reporte no encontrado.',
                'RESOURCE_NOT_FOUND',
                404
            );
        }

        if ($reporte->id_estado_reporte === 3) {
            return $this->error(
                'El reporte ya fue resuelto.',
                'REPORT_ALREADY_RESOLVED',
                409
            );
        }

        $transicionValida =
            ($reporte->id_estado_reporte === 1 &&
                in_array($nuevoEstado, [2, 3], true))
            ||
            ($reporte->id_estado_reporte === 2 &&
                $nuevoEstado === 3);

        if (! $transicionValida) {
            return $this->error(
                'La transición solicitada no es válida.',
                'OPERATION_FAILED',
                409
            );
        }

        DB::transaction(function () use (
            $request,
            $reporte,
            $nuevoEstado,
            $resolucion
        ) {

            $reporte->id_estado_reporte = $nuevoEstado;

            $reporte->id_admin_moderador =
                $request->user()->id_usuario;

            if ($nuevoEstado === 3) {

                $reporte->resolucion =
                    $resolucion;

                $reporte->fecha_resolucion =
                    now();
            }

            $reporte->save();
        });

        $reporte->load(self::RELACIONES);

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => new AdminReporteResource(
                $reporte
            ),
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
