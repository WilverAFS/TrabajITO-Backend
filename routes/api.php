<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PerfilProfesionalController;
use App\Http\Controllers\Api\V1\DisponibilidadController;
use App\Http\Controllers\Api\V1\CertificadoController;
use App\Http\Controllers\Api\V1\SolicitudHabilitacionController;
use App\Http\Controllers\Api\V1\TrabajadorController;
use App\Http\Controllers\Api\V1\ContratacionController;
use App\Http\Controllers\Api\V1\ContratacionTransicionController;
use App\Http\Controllers\Api\V1\PublicacionController;
use App\Http\Controllers\Api\V1\EvidenciaController;

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

    // API 19 - Exploración pública de trabajadores

    // Listar y buscar trabajadores habilitados
    Route::get(
        '/trabajadores',
        [TrabajadorController::class, 'index']
    );

    // Consultar el perfil público de un trabajador
    Route::get(
        '/trabajadores/{id_trabajador}',
        [TrabajadorController::class, 'show']
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

    // Consultar una publicación específica
    Route::get(
        '/publicaciones/{id_publicacion}',
        [PublicacionController::class, 'show']
    );

    // Consultar publicaciones visibles de un trabajador
    Route::get(
        '/publicaciones/trabajador/{id_trabajador}',
        [PublicacionController::class, 'publicacionesPorTrabajador']
    );


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

        // Actualizar perfil profesional propio
        Route::patch(
            '/me/perfil',
            [PerfilProfesionalController::class, 'update']
        );

        // Actualizar oficios del perfil profesional
        Route::put(
            '/me/perfil/oficios',
            [PerfilProfesionalController::class, 'updateOficios']
        );

        // Disponibilidad del trabajador
        // Consultar disponibilidades propias
        Route::get(
            '/me/perfil/disponibilidades',
            [DisponibilidadController::class, 'index']
        );

        // Registrar disponibilidad
        Route::post(
            '/me/perfil/disponibilidades',
            [DisponibilidadController::class, 'store']
        );

        // Actualizar disponibilidad
        Route::patch(
            '/me/perfil/disponibilidades/{id_disponibilidad}',
            [DisponibilidadController::class, 'update']
        );

        // Eliminar disponibilidad
        Route::delete(
            '/me/perfil/disponibilidades/{id_disponibilidad}',
            [DisponibilidadController::class, 'destroy']
        );

        // Certificados del trabajador
        // Consultar certificados propios
        Route::get(
            '/me/perfil/certificados',
            [CertificadoController::class, 'index']
        );

        // Registrar certificado
        Route::post(
            '/me/perfil/certificados',
            [CertificadoController::class, 'store']
        );


        // Eliminar certificado
        Route::delete(
            '/me/perfil/certificados/{id_certificado}',
            [CertificadoController::class, 'destroy']
        );

        // API 18 - Solicitudes de habilitación

        // Enviar solicitud de habilitación
        Route::post(
            '/me/perfil/solicitudes-habilitacion',
            [SolicitudHabilitacionController::class, 'store']
        );

        // Consultar historial de solicitudes propias
        Route::get(
            '/me/perfil/solicitudes-habilitacion',
            [SolicitudHabilitacionController::class, 'index']
        );

        // API 20 - Contrataciones

        // Crear solicitud de contratación
        Route::post(
            '/contrataciones',
            [ContratacionController::class, 'store']
        );

        // Listar contrataciones propias
        Route::get(
            '/contrataciones',
            [ContratacionController::class, 'index']
        );

        // Consultar una contratación específica
        Route::get(
            '/contrataciones/{id_contratacion}',
            [ContratacionController::class, 'show']
        );

        /**
         * Transiciones de contratacion
         */
        Route::post(
            '/contrataciones/{contratacion:id_contratacion}/aceptar',
            [ContratacionTransicionController::class, 'aceptar']
        );

        Route::post(
            '/contrataciones/{contratacion:id_contratacion}/rechazar',
            [ContratacionTransicionController::class, 'rechazar']
        );

        Route::post(
            '/contrataciones/{contratacion:id_contratacion}/iniciar',
            [ContratacionTransicionController::class, 'iniciar']
        );

        Route::post(
            '/contrataciones/{contratacion:id_contratacion}/finalizar',
            [ContratacionTransicionController::class, 'finalizar']
        );

        Route::post(
            '/contrataciones/{contratacion:id_contratacion}/cancelar',
            [ContratacionTransicionController::class, 'cancelar']
        );

        /**
         * Publicaciones
         */
        // Crear publicación asociada a una contratación finalizada
        Route::post(
            '/contrataciones/{id_contratacion}/publicacion',
            [PublicacionController::class, 'store']
        );

        // Actualizar publicación propia
        Route::patch(
            '/publicaciones/{id_publicacion}',
            [PublicacionController::class, 'update']
        );

        // Consultar publicaciones propias
        Route::get(
            '/me/perfil/publicaciones',
            [PublicacionController::class, 'misPublicaciones']
        );

        // Ocultar publicación (administrador)
        Route::patch(
            '/admin/publicaciones/{id_publicacion}/ocultar',
            [PublicacionController::class, 'ocultar']
        );

        /**
         * EVIDENCIAS
         */
        // Cargar evidencias a una publicación
        Route::post(
            '/publicaciones/{id_publicacion}/evidencias',
            [EvidenciaController::class, 'store']
        );

        // Eliminar evidencia
        Route::delete(
            '/evidencias/{id_evidencia}',
            [EvidenciaController::class, 'destroy']
        );
    });
});
