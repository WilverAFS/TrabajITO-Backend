<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CrearReporteRequest;
use App\Http\Resources\Api\V1\ReporteResource;
use App\Models\EstadoReporte;
use App\Models\PerfilProfesional;
use App\Models\Publicacion;
use App\Models\Reporte;
use Illuminate\Http\JsonResponse;

class ReporteController extends Controller
{
    /**
     * POST /api/v1/reportes
     */
    public function store(
        CrearReporteRequest $request
    ): JsonResponse {

        $datos = $request->validated();

        $tipoObjetivo = $datos['tipo_objetivo'];
        $idObjetivo = $datos['id_objetivo'];

        // Verificar que exista la publicación o el perfil.
        if ($tipoObjetivo === 'PUBLICACION') {

            $existe = Publicacion::query()
                ->where('id_publicacion', $idObjetivo)
                ->exists();

        } else {

            $existe = PerfilProfesional::query()
                ->where('id_trabajador', $idObjetivo)
                ->exists();
        }

        if (! $existe) {
            return $this->error(
                'El objetivo del reporte no existe.',
                'REPORT_TARGET_NOT_FOUND',
                404
            );
        }

        // Obtener el estado inicial Pendiente.
        $estadoPendiente = EstadoReporte::query()
            ->conNombre('Pendiente')
            ->first();

        if (! $estadoPendiente) {
            return $this->error(
                'No se encuentra configurado el estado Pendiente.',
                'REPORT_STATUS_NOT_CONFIGURED',
                500
            );
        }

        // Obtener al denunciante del usuario autenticado.
        $usuario = $request->user();

        // Crear el reporte.
        $reporte = Reporte::create([
            'id_denunciante' => $usuario->id_usuario,
            'tipo_objetivo' => $tipoObjetivo,
            'id_objetivo' => $idObjetivo,
            'motivo' => $datos['motivo'],
            'descripcion' => $datos['descripcion'],
            'id_estado_reporte' =>
                $estadoPendiente->id_estado_reporte,
            'fecha_creacion' => now(),
        ]);

        // Cargar el estado para el Resource.
        $reporte->load('estado');

        return response()->json([
            'success' => true,
            'message' => 'Reporte enviado correctamente.',
            'data' => new ReporteResource($reporte),
        ], 201);
    }

    /**
     * Respuestas de error.
     */
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
