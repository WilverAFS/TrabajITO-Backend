<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PerfilProfesionalController;

Route::prefix('v1')->group(function () {

    // Registro
    Route::post(
        '/auth/register',
        [AuthController::class, 'register']
    )->middleware('throttle:5,1');

    // Inicio de sesión
    Route::post(
        '/auth/login',
        [AuthController::class, 'login']
    )->middleware('throttle:5,1');

    // Catálogos públicos
    Route::get(
        '/estados',
        [CatalogoController::class, 'estados']
    );

    Route::get(
        '/estados/{id_estado}/municipios',
        [CatalogoController::class, 'municipios']
    );

    Route::get(
        '/oficios',
        [CatalogoController::class, 'oficios']
    );

    //Recuperar contraseña olvidada
    Route::post(
        '/auth/forgot-password',
        [AccountController::class, 'forgotPassword']
    )->middleware('throttle:5,1');

    //Reestrablecer contraseña
    Route::post(
        '/auth/reset-password',
        [AccountController::class, 'resetPassword']
    )->middleware('throttle:5,1');


    // Rutas que requieren autenticación
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me']);

        //Actualizar cuenta
        Route::patch(
            '/me',
            [AccountController::class, 'update']
        );

        //Cambiar contraseña
        Route::patch(
            '/me/password',
            [AccountController::class, 'changePassword']
        );

        Route::post(
            '/auth/logout',
            [AuthController::class, 'logout']
        );

        Route::get(
            '/tipos-publicacion',
            [CatalogoController::class, 'tiposPublicacion']
        );

        // Perfil profesional
        Route::post(
            '/perfiles',
            [PerfilProfesionalController::class, 'store']
        );

        Route::get(
            '/me/perfil',
            [PerfilProfesionalController::class, 'show']
        );
    });
});
