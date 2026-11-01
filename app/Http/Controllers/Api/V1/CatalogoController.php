<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EstadoResource;
use App\Http\Resources\Api\V1\MunicipioResource;
use App\Http\Resources\Api\V1\OficioResource;
use App\Http\Resources\Api\V1\TipoPublicacionResource;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Oficio;
use App\Models\TipoPublicacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    /**
     * Obtener los estados activos.
     */
    public function estados(): JsonResponse
    {
        $estados = Estado::query()
            ->where('activo', true)
            ->orderBy('estado')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Estados obtenidos correctamente.',
            'data' => EstadoResource::collection($estados),
        ]);
    }

    /**
     * Obtener los municipios activos de un estado.
     */
    public function municipios(int $id_estado): JsonResponse
    {
        $municipios = Municipio::query()
            ->where('activo', true)
            ->where('id_estado', $id_estado)
            ->orderBy('municipio')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Municipios obtenidos correctamente.',
            'data' => MunicipioResource::collection($municipios),
        ]);
    }

    /**
     * Obtener los oficios activos y permitir búsqueda por nombre.
     */
    public function oficios(Request $request): JsonResponse
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $consulta = Oficio::query()
            ->where('activo', true);

        if ($buscar !== '') {
            $consulta->whereRaw(
                'LOWER(nombre) LIKE ?',
                ['%' . mb_strtolower($buscar) . '%']
            );
        }

        $oficios = $consulta
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Oficios obtenidos correctamente.',
            'data' => OficioResource::collection($oficios),
        ]);
    }

    /**
     * Obtener los tipos de publicación si el usuario tiene
     * un perfil profesional habilitado.
     */
    public function tiposPublicacion(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if (!$usuario instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'No estás autenticado.',
                'data' => null,
            ], 401);
        }

        $perfilHabilitado = $usuario
            ->perfilProfesional()
            ->where('activo', true)
            ->exists();

        if (!$perfilHabilitado) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un perfil profesional habilitado.',
                'data' => null,
            ], 403);
        }

        $tipos = TipoPublicacion::query()
            ->orderBy('tipo_publicacion')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Tipos de publicación obtenidos correctamente.',
            'data' => TipoPublicacionResource::collection($tipos),
        ]);
    }
}
