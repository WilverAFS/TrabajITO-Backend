<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePerfilProfesionalRequest;
use App\Http\Resources\Api\V1\PerfilProfesionalResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerfilProfesionalController extends Controller
{
    //Crear perfil profesional
    public function store(
        StorePerfilProfesionalRequest $request
    ): JsonResponse {

        $usuario = $request->user();

        // Verificar si el usuario ya tiene un perfil
        if ($usuario->perfilProfesional()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya tiene un perfil profesional.',
                'error_code' => 'PROFILE_ALREADY_EXISTS',
                'errors' => null,
            ], 409);
        }

        $datos = $request->validated();

        // Crear perfil y asociar oficios en una transacción
        $perfil = DB::transaction(function () use ($usuario, $datos) {

            $perfil = $usuario->perfilProfesional()->create([
                'id_municipio' => $datos['id_municipio'],
                'descripcion' => $datos['descripcion'],
            ]);

            $perfil->oficios()->sync($datos['oficios']);

            return $perfil->load([
                'municipio.estado',
                'oficios',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Perfil profesional creado correctamente.',
            'data' => new PerfilProfesionalResource($perfil),
        ], 201);
    }

    // Consultar perfil del usuario autenticado
    public function show(Request $request): JsonResponse
    {
        $perfil = $request->user()
            ->perfilProfesional()
            ->with([
                'municipio.estado',
                'oficios',
            ])
            ->first();

        if (!$perfil) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un perfil profesional registrado.',
                'error_code' => 'PROFILE_NOT_FOUND',
                'errors' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil profesional obtenido correctamente.',
            'data' => new PerfilProfesionalResource($perfil),
        ]);
    }
}