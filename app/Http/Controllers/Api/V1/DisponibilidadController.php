<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDisponibilidadRequest;
use App\Http\Requests\Api\V1\UpdateDisponibilidadRequest;
use App\Http\Resources\Api\V1\DisponibilidadResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisponibilidadController extends Controller
{
    // Respuesta estandarizada para errores
    private function respuestaError(
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

    // Normalizar las horas al formato HH:MM:SS
    private function normalizarHora(string $hora): string
    {
        $hora = substr($hora, 0, 8);

        return strlen($hora) === 5
            ? $hora . ':00'
            : $hora;
    }

    // Consultar las disponibilidades del trabajador autenticado
    public function index(Request $request): JsonResponse
    {
        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (!$perfil) {
            return $this->respuestaError(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        $disponibilidades = $perfil->disponibilidades()
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Disponibilidades obtenidas correctamente.',
            'data' => DisponibilidadResource::collection(
                $disponibilidades
            )->resolve($request),
        ], 200);
    }

    // Registrar un nuevo intervalo de disponibilidad
    public function store(
        StoreDisponibilidadRequest $request
    ): JsonResponse {

        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (!$perfil) {
            return $this->respuestaError(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        $datos = $request->validated();

        $horaInicio = $this->normalizarHora(
            $datos['hora_inicio']
        );

        $horaFin = $this->normalizarHora(
            $datos['hora_fin']
        );

        // Verificar que no existan intervalos superpuestos.
        $haySuperposicion = $perfil->disponibilidades()
            ->where('dia_semana', $datos['dia_semana'])
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->exists();

        if ($haySuperposicion) {
            return $this->respuestaError(
                'Los datos proporcionados no son válidos.',
                'VALIDATION_ERROR',
                422,
                [
                    'hora_inicio' => [
                        'El intervalo se superpone con otro horario registrado para ese día.',
                    ],
                ]
            );
        }

        $disponibilidad = $perfil->disponibilidades()->create([
            'dia_semana' => $datos['dia_semana'],
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'activo' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disponibilidad registrada correctamente.',
            'data' => new DisponibilidadResource($disponibilidad),
        ], 201);
    }

    // Actualizar parcialmente un intervalo de disponibilidad
    public function update(
        UpdateDisponibilidadRequest $request,
        int $id_disponibilidad
    ): JsonResponse {

        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (!$perfil) {
            return $this->respuestaError(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        // Buscar exclusivamente dentro del perfil autenticado.
        $disponibilidad = $perfil->disponibilidades()
            ->whereKey($id_disponibilidad)
            ->first();

        if (!$disponibilidad) {
            return $this->respuestaError(
                'La disponibilidad solicitada no existe.',
                'AVAILABILITY_NOT_FOUND',
                404
            );
        }

        $datos = $request->validated();

        // Combinar los datos actuales con los nuevos.
        $datosFinales = array_merge(
            $disponibilidad->only([
                'dia_semana',
                'hora_inicio',
                'hora_fin',
                'activo',
            ]),
            $datos
        );

        $horaInicio = $this->normalizarHora(
            (string) $datosFinales['hora_inicio']
        );

        $horaFin = $this->normalizarHora(
            (string) $datosFinales['hora_fin']
        );

        // Comprobar que la hora final sea posterior a la inicial.
        if ($horaFin <= $horaInicio) {
            return $this->respuestaError(
                'Los datos proporcionados no son válidos.',
                'VALIDATION_ERROR',
                422,
                [
                    'hora_fin' => [
                        'La hora de fin debe ser posterior a la hora de inicio.',
                    ],
                ]
            );
        }

        // Comprobar superposición excluyendo el intervalo actual.
        $haySuperposicion = $perfil->disponibilidades()
            ->where('dia_semana', $datosFinales['dia_semana'])
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->where(
                'id_disponibilidad',
                '!=',
                $id_disponibilidad
            )
            ->exists();

        if ($haySuperposicion) {
            return $this->respuestaError(
                'Los datos proporcionados no son válidos.',
                'VALIDATION_ERROR',
                422,
                [
                    'hora_inicio' => [
                        'El intervalo se superpone con otro horario registrado para ese día.',
                    ],
                ]
            );
        }

        $disponibilidad->update([
            'dia_semana' => $datosFinales['dia_semana'],
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'activo' => $datosFinales['activo'],
        ]);

        $disponibilidad->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Disponibilidad actualizada correctamente.',
            'data' => new DisponibilidadResource($disponibilidad),
        ], 200);
    }

    // Eliminar un intervalo de disponibilidad.
    public function destroy(
        Request $request,
        int $id_disponibilidad
    ): Response {

        $perfil = $request->user()
            ->perfilProfesional()
            ->first();

        if (!$perfil) {
            return $this->respuestaError(
                'No tienes un perfil profesional registrado.',
                'PROFILE_NOT_FOUND',
                404
            );
        }

        // Buscar exclusivamente dentro del perfil autenticado.
        $disponibilidad = $perfil->disponibilidades()
            ->whereKey($id_disponibilidad)
            ->first();

        if (!$disponibilidad) {
            return $this->respuestaError(
                'La disponibilidad solicitada no existe.',
                'AVAILABILITY_NOT_FOUND',
                404
            );
        }

        $disponibilidad->delete();

        // El contrato establece 204 sin cuerpo JSON.
        return response()->noContent();
    }
}
