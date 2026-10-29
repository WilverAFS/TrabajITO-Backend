<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UsuarioLoginResource;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $datos = $request->validated();

        [$usuario, $token] = DB::transaction(
            function () use ($datos): array {
                $usuario = User::create([
                    'nombre' => $datos['nombre'],
                    'apellido' => $datos['apellido'],
                    'correo' => $datos['correo'],
                    'telefono' => $datos['telefono'] ?? null,
                    'contrasena_hash' => Hash::make(
                        $datos['password']
                    ),
                ]);

                $usuario->refresh();

                $token = $usuario
                    ->createToken('TrabajITO Web')
                    ->plainTextToken;

                return [$usuario, $token];
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Cuenta registrada correctamente.',
            'data' => [
                'usuario' => (new UsuarioResource($usuario))
                    ->resolve($request),
                'token' => $token,
            ],
        ], 201);
    }
    public function login(LoginRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $usuario = User::query()
            ->where('correo', $datos['correo'])
            ->first();

        // Credenciales incorrectas
        if (
            !$usuario
            || !Hash::check(
                $datos['password'],
                $usuario->contrasena_hash
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
                'error_code' => 'INVALID_CREDENTIALS',
                'errors' => null,
            ], 401);
        }

        // Cuenta inactiva
        if (!$usuario->activo) {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta esta inactiva.',
                'error_code' => 'ACCOUNT_INACTIVE',
                'errors' => null,
            ], 403);
        }

        // Crear un token personal de Sanctum
        $token = $usuario
            ->createToken($datos['device_name'])
            ->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesion exitoso.',
            'data' => [
                'usuario' => UsuarioLoginResource::make($usuario)
                    ->resolve($request),
                'token' => $token,
            ],
        ], 200);
    }
}
