
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogoController;
use Illuminate\Support\Facades\Route;

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

    // Rutas que requieren autenticación
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me']);

        Route::post(
            '/auth/logout',
            [AuthController::class, 'logout']
        );

        Route::get(
            '/tipos-publicacion',
            [CatalogoController::class, 'tiposPublicacion']
        );
    });
});
