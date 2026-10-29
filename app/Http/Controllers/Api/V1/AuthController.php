<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
}
