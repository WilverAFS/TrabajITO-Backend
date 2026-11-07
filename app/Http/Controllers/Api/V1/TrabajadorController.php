<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BuscarTrabajadoresRequest;
use App\Http\Resources\Api\V1\PerfilProfesionalResumenResource;
use App\Http\Resources\Api\V1\PerfilProfesionalPublicoResource;
use App\Models\PerfilProfesional;
use App\Models\Publicacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrabajadorController extends Controller
{
    /**
     * GET /api/v1/trabajadores
     *
     * Lista pública con filtros y paginación.
     */
    public function index(
        BuscarTrabajadoresRequest $request
    ): JsonResponse {
        $filtros = $request->validated();

        $consulta = $this->agregarReputacion(
            PerfilProfesional::query()
        )
            ->with([
                'usuario',
                'municipio.estado',
                'oficios',
            ])
            ->where('perfiles_profesionales.activo', true)
            ->whereHas('usuario', function (Builder $query) {
                $query->where('activo', true);
            });

        // Buscar por descripción, usuario u oficio.
        if (!empty($filtros['buscar'])) {
            $termino = '%' . mb_strtolower(
                trim($filtros['buscar']),
                'UTF-8'
            ) . '%';

            $consulta->where(function (Builder $query) use ($termino) {
                $query
                    ->whereRaw(
                        'LOWER(perfiles_profesionales.descripcion) LIKE ?',
                        [$termino]
                    )
                    ->orWhereHas('usuario', function (Builder $usuario) use ($termino) {
                        $usuario
                            ->whereRaw(
                                'LOWER(users.nombre) LIKE ?',
                                [$termino]
                            )
                            ->orWhereRaw(
                                'LOWER(users.apellido) LIKE ?',
                                [$termino]
                            )
                            ->orWhereRaw(
                                "LOWER(users.nombre || ' ' || users.apellido) LIKE ?",
                                [$termino]
                            );
                    })
                    ->orWhereHas('oficios', function (Builder $oficio) use ($termino) {
                        $oficio->whereRaw(
                            'LOWER(oficios.nombre) LIKE ?',
                            [$termino]
                        );
                    });
            });
        }

        // Filtrar por oficio.
        if (isset($filtros['id_oficio'])) {
            $consulta->whereHas(
                'oficios',
                function (Builder $query) use ($filtros) {
                    $query->where(
                        'oficios.id_oficio',
                        $filtros['id_oficio']
                    );
                }
            );
        }

        // Filtrar por municipio.
        if (isset($filtros['id_municipio'])) {
            $consulta->where(
                'perfiles_profesionales.id_municipio',
                $filtros['id_municipio']
            );
        }

        // Mostrar trabajadores disponibles el día solicitado.
        if (isset($filtros['dia_semana'])) {
            $consulta->whereHas(
                'disponibilidades',
                function (Builder $query) use ($filtros) {
                    $query
                        ->where('activo', true)
                        ->where(
                            'dia_semana',
                            $filtros['dia_semana']
                        );
                }
            );
        }

        // Filtrar por calificación promedio mínima.
        if (isset($filtros['calificacion_minima'])) {
            $consulta->whereRaw(
                '(
                    SELECT AVG(v.calificacion)
                    FROM valoraciones AS v
                    INNER JOIN publicaciones AS p
                        ON p.id_publicacion = v.id_publicacion
                    INNER JOIN contrataciones AS c
                        ON c.id_contratacion = p.id_contratacion
                    WHERE c.id_trabajador =
                        perfiles_profesionales.id_trabajador
                ) >= ?',
                [(float) $filtros['calificacion_minima']]
            );
        }

        // Ordenamiento permitido por el contrato.
        $sort = $filtros['sort'] ?? 'fecha_registro';
        $direction = $filtros['direction'] ?? 'desc';

        if ($sort === 'reputacion') {
            $consulta->orderBy(
                'calificacion_promedio',
                $direction
            );
        } else {
            $consulta->orderBy(
                'perfiles_profesionales.fecha_registro',
                $direction
            );
        }

        // Desempate para mantener un orden estable.
        $consulta->orderBy(
            'perfiles_profesionales.id_trabajador',
            'asc'
        );

        $perPage = (int) ($filtros['per_page'] ?? 15);

        $paginador = $consulta
            ->paginate($perPage)
            ->appends($filtros);

        return response()->json([
            'success' => true,
            'message' => 'Trabajadores obtenidos correctamente.',
            'data' => PerfilProfesionalResumenResource::collection(
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
     * GET /api/v1/trabajadores/{id_trabajador}
     *
     * Consultar el detalle público de un trabajador.
     */
    public function show(
        Request $request,
        int $id_trabajador
    ): JsonResponse {
        $perfil = $this->agregarReputacion(
            PerfilProfesional::query()
        )
            ->with([
                'usuario',
                'municipio.estado',
                'oficios',

                'disponibilidades' => function ($query) {
                    $query
                        ->where('activo', true)
                        ->orderBy('dia_semana')
                        ->orderBy('hora_inicio');
                },

                'certificados' => function ($query) {
                    $query->where('verificado', true);
                },
            ])
            ->where('perfiles_profesionales.activo', true)
            ->whereHas('usuario', function (Builder $query) {
                $query->where('activo', true);
            })
            ->where(
                'perfiles_profesionales.id_trabajador',
                $id_trabajador
            )
            ->first();

        if (!$perfil) {
            return response()->json([
                'success' => false,
                'message' => 'Trabajador no encontrado.',
                'error_code' => 'WORKER_NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        // Cargar solo publicaciones visibles del trabajador.
        $publicaciones = Publicacion::query()
            ->visibles()
            ->whereHas(
                'contratacion',
                function (Builder $query) use ($perfil) {
                    $query->where(
                        'id_trabajador',
                        $perfil->id_trabajador
                    );
                }
            )
            ->orderByDesc('fecha_publicacion')
            ->get();

        // Colección pública utilizada por el Resource.
        $perfil->setRelation(
            'publicacionesPublicas',
            $publicaciones
        );

        return response()->json([
            'success' => true,
            'message' => 'Trabajador obtenido correctamente.',
            'data' => (
                new PerfilProfesionalPublicoResource($perfil)
            )->resolve($request),
        ]);
    }

    /**
     * Subconsulta base de valoraciones por trabajador.
     */
    private function consultaValoraciones(): QueryBuilder
    {
        return DB::table('valoraciones AS v')
            ->join(
                'publicaciones AS p',
                'p.id_publicacion',
                '=',
                'v.id_publicacion'
            )
            ->join(
                'contrataciones AS c',
                'c.id_contratacion',
                '=',
                'p.id_contratacion'
            )
            ->whereColumn(
                'c.id_trabajador',
                'perfiles_profesionales.id_trabajador'
            );
    }

    /**
     * Añadir promedio y cantidad de valoraciones.
     */
    private function agregarReputacion(
        Builder $consulta
    ): Builder {
        return $consulta
            ->select('perfiles_profesionales.*')
            ->selectSub(
                $this->consultaValoraciones()
                    ->selectRaw('AVG(v.calificacion)'),
                'calificacion_promedio'
            )
            ->selectSub(
                $this->consultaValoraciones()
                    ->selectRaw('COUNT(*)'),
                'total_valoraciones'
            );
    }
}
