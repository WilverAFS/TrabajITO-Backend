<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SolicitudHabilitacionResource;
use App\Models\PerfilProfesional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudHabilitacionController extends Controller
{
    // Crear solicitud de habilitación
    public function store(Request $request): JsonResponse
    {
        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (!$perfil) {
            return $this->error(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        // Bloquear el perfil durante la comprobación y creación
        // para evitar solicitudes simultáneas duplicadas.
        $resultado = DB::transaction(function () use ($perfil) {
            $perfilActual = PerfilProfesional::query()
                ->whereKey($perfil->id_trabajador)
                ->lockForUpdate()
                ->firstOrFail();

            if ($perfilActual->activo) {
                return 'habilitado';
            }

            $tienePendiente = $perfilActual
                ->solicitudesHabilitacion()
                ->whereIn('id_estado_solicitud', [1, 2])
                ->exists();

            if ($tienePendiente) {
                return 'pendiente';
            }

            // El modelo establece el estado inicial en 1.
            // La base de datos genera fecha_solicitud.
            return $perfilActual
                ->solicitudesHabilitacion()
                ->create([])
                ->refresh();
        });

        if ($resultado === 'habilitado') {
            return $this->error(
                'Tu perfil profesional ya está habilitado.',
                'PROFILE_ALREADY_ENABLED',
                409
            );
        }

        if ($resultado === 'pendiente') {
            return $this->error(
                'Ya tienes una solicitud pendiente o en revisión.',
                'PENDING_ENABLEMENT_REQUEST',
                409
            );
        }

        $resultado->load('estadoSolicitud');

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de habilitación enviada correctamente.',
            'data' => new SolicitudHabilitacionResource(
                $resultado,
                true
            ),
        ], 201);
    }

    // Consultar historial propio
    public function index(Request $request): JsonResponse
    {
        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (!$perfil) {
            return $this->error(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        $solicitudes = $perfil
            ->solicitudesHabilitacion()
            ->with([
                'estadoSolicitud',
                'administradorRevisor',
            ])
            ->orderByDesc('fecha_solicitud')
            ->orderByDesc('id_solicitud')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Solicitudes de habilitación obtenidas correctamente.',
            'data' => SolicitudHabilitacionResource::collection(
                $solicitudes
            ),
        ], 200);
    }

    // Respuestas estandarizadas de error
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
