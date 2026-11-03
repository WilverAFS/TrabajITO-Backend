<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Http\Requests\Api\V1\UpdateAccountRequest;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;

use App\Http\Resources\Api\V1\UsuarioAutenticadoResource;

use App\Models\User;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AccountController extends Controller
{
    /**
     * Actualizar información básica de la cuenta.
     */
    public function update(
        UpdateAccountRequest $request
    ): JsonResponse {

        $usuario = $request->user();

        $usuario->update(
            $request->validated()
        );

        $usuario->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Datos de la cuenta actualizados correctamente.',
            'data' => UsuarioAutenticadoResource::make(
                $usuario
            )->resolve($request),
        ], 200);
    }

    /**
     * Cambiar contraseña del usuario autenticado.
     */
    public function changePassword(
        ChangePasswordRequest $request
    ): JsonResponse {

        $usuario = $request->user();

        if (
            !Hash::check(
                $request->password_actual,
                $usuario->contrasena_hash
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual no es correcta.',
                'error_code' => 'INVALID_CURRENT_PASSWORD',
                'errors' => null,
            ], 422);
        }

        $usuario->update([
            'contrasena_hash' => $request->password,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
            'data' => null,
        ], 200);
    }

    /**
     * Solicitar recuperación de contraseña.
     */
    public function forgotPassword(
        ForgotPasswordRequest $request
    ): JsonResponse {

        Password::sendResetLink([
            'correo' => $request->correo,
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            'Si el correo existe, se enviaron instrucciones para restablecer la contraseña.',
            'data' => null,
        ], 200);
    }

    /**
     * Restablecer contraseña mediante token.
     */
    public function resetPassword(
        ResetPasswordRequest $request
    ): JsonResponse {

        $status = Password::reset(
            [
                'correo' => $request->correo,
                'password' => $request->password,
                'password_confirmation'
                => $request->password_confirmation,
                'token' => $request->token,
            ],
            function (
                User $usuario,
                string $password
            ): void {

                $usuario->update([
                    'contrasena_hash' => $password,
                ]);

                // Revocar todos los tokens activos
                $usuario->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {

            return response()->json([
                'success' => false,
                'message' =>
                'El token de recuperación es inválido o expiró.',
                'error_code' => 'INVALID_RESET_TOKEN',
                'errors' => null,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' =>
            'Contraseña restablecida correctamente.',
            'data' => null,
        ], 200);
    }
}
