<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UsuarioAutenticadoResource;
use App\Http\Resources\Api\V1\UsuarioLoginResource;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
                    'contrasena_hash' => Hash::make($datos['password']),
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
                'usuario' => (new UsuarioResource($usuario))->resolve($request),
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $usuario = User::query()
            ->whereRaw('LOWER(correo) = ?', [
                mb_strtolower($datos['correo'], 'UTF-8')
            ])
            ->first();

        // Se conserva la compatibilidad con cuentas que tengan contraseñas heredadas.
        if (
            ! $usuario ||
            ! Hash::check($datos['password'], $usuario->contrasena_hash)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
                'error_code' => 'INVALID_CREDENTIALS',
                'errors' => null,
            ], 401);
        }

        if (! $usuario->activo) {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta esta inactiva.',
                'error_code' => 'ACCOUNT_INACTIVE',
                'errors' => null,
            ], 403);
        }

        $token = $usuario
            ->createToken($datos['device_name'] ?? 'TrabajITO Web')
            ->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesion exitoso.',
            'data' => [
                'usuario' => UsuarioLoginResource::make($usuario)->resolve($request),
                'token' => $token,
            ],
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Usuario autenticado obtenido correctamente.',
            'data' => UsuarioAutenticadoResource::make($usuario)->resolve($request),
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
            'data' => null,
        ], 200);
    }
}
