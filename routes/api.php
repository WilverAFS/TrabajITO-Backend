<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AdminOficioController;
use App\Http\Controllers\Api\V1\AdminReporteController;
use App\Http\Controllers\Api\V1\AdminSolicitudHabilitacionController;
use App\Http\Controllers\Api\V1\AdminUsuarioController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\CertificadoController;
use App\Http\Controllers\Api\V1\ContratacionController;
use App\Http\Controllers\Api\V1\ContratacionTransicionController;
use App\Http\Controllers\Api\V1\DisponibilidadController;
use App\Http\Controllers\Api\V1\EvidenciaController;
use App\Http\Controllers\Api\V1\NotificacionController;
use App\Http\Controllers\Api\V1\PerfilProfesionalController;
use App\Http\Controllers\Api\V1\PublicacionController;
use App\Http\Controllers\Api\V1\ReporteController;
use App\Http\Controllers\Api\V1\SolicitudHabilitacionController;
use App\Http\Controllers\Api\V1\TrabajadorController;
use App\Http\Controllers\Api\V1\ValoracionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Registro e inicio de sesión.
    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // Catálogos públicos.
    Route::get('/estados', [CatalogoController::class, 'estados']);
    Route::get('/estados/{id_estado}/municipios', [CatalogoController::class, 'municipios']);
    Route::get('/oficios', [CatalogoController::class, 'oficios']);

    // API 19: exploración pública de trabajadores.
    Route::get('/trabajadores', [TrabajadorController::class, 'index']);
    Route::get('/trabajadores/{id_trabajador}', [TrabajadorController::class, 'show']);

    // Recuperación de contraseña.
    Route::post('/auth/forgot-password', [AccountController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [AccountController::class, 'resetPassword'])
        ->middleware('throttle:5,1');

    // Consultas públicas de publicaciones.
    Route::get('/publicaciones/{id_publicacion}', [PublicacionController::class, 'show']);
    Route::get('/publicaciones/trabajador/{id_trabajador}', [PublicacionController::class, 'publicacionesPorTrabajador']);

    // Rutas protegidas.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me', [AccountController::class, 'update']);
        Route::patch('/me/password', [AccountController::class, 'changePassword']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/tipos-publicacion', [CatalogoController::class, 'tiposPublicacion']);

        // Perfil profesional.
        Route::post('/perfiles', [PerfilProfesionalController::class, 'store']);
        Route::get('/me/perfil', [PerfilProfesionalController::class, 'show']);
        Route::patch('/me/perfil', [PerfilProfesionalController::class, 'update']);
        Route::put('/me/perfil/oficios', [PerfilProfesionalController::class, 'updateOficios']);

        // Disponibilidades.
        Route::get('/me/perfil/disponibilidades', [DisponibilidadController::class, 'index']);
        Route::post('/me/perfil/disponibilidades', [DisponibilidadController::class, 'store']);
        Route::patch('/me/perfil/disponibilidades/{id_disponibilidad}', [DisponibilidadController::class, 'update']);
        Route::delete('/me/perfil/disponibilidades/{id_disponibilidad}', [DisponibilidadController::class, 'destroy']);

        // Certificados.
        Route::get('/me/perfil/certificados', [CertificadoController::class, 'index']);
        Route::post('/me/perfil/certificados', [CertificadoController::class, 'store']);
        Route::delete('/me/perfil/certificados/{id_certificado}', [CertificadoController::class, 'destroy']);

        // API 18: solicitudes propias de habilitación.
        Route::post('/me/perfil/solicitudes-habilitacion', [SolicitudHabilitacionController::class, 'store']);
        Route::get('/me/perfil/solicitudes-habilitacion', [SolicitudHabilitacionController::class, 'index']);

        // API 20: contrataciones.
        Route::post('/contrataciones', [ContratacionController::class, 'store']);
        Route::get('/contrataciones', [ContratacionController::class, 'index']);
        Route::get('/contrataciones/{id_contratacion}', [ContratacionController::class, 'show']);
        Route::patch('/contrataciones/{contratacion:id_contratacion}/aceptar', [ContratacionTransicionController::class, 'aceptar']);
        Route::patch('/contrataciones/{contratacion:id_contratacion}/rechazar', [ContratacionTransicionController::class, 'rechazar']);
        Route::patch('/contrataciones/{contratacion:id_contratacion}/iniciar', [ContratacionTransicionController::class, 'iniciar']);
        Route::patch('/contrataciones/{contratacion:id_contratacion}/finalizar', [ContratacionTransicionController::class, 'finalizar']);
        Route::patch('/contrataciones/{contratacion:id_contratacion}/cancelar', [ContratacionTransicionController::class, 'cancelar']);

        // Publicaciones y evidencias.
        Route::post('/contrataciones/{id_contratacion}/publicacion', [PublicacionController::class, 'store']);
        Route::patch('/publicaciones/{id_publicacion}', [PublicacionController::class, 'update']);
        Route::get('/me/perfil/publicaciones', [PublicacionController::class, 'misPublicaciones']);
        Route::patch('/admin/publicaciones/{id_publicacion}/ocultar', [PublicacionController::class, 'ocultar']);
        Route::post('/publicaciones/{id_publicacion}/evidencias', [EvidenciaController::class, 'store']);
        Route::delete('/evidencias/{id_evidencia}', [EvidenciaController::class, 'destroy']);

        // API 24: valoraciones.
        Route::post('/publicaciones/{id_publicacion}/valoracion', [ValoracionController::class, 'store']);
        Route::patch('/valoraciones/{id_valoracion}', [ValoracionController::class, 'update']);

        // API 25: reportes.
        Route::post('/reportes', [ReporteController::class, 'store']);

        // API 26: notificaciones propias.
        Route::get('/notificaciones/no-leidas/count', [NotificacionController::class, 'countNoLeidas']);
        Route::get('/notificaciones', [NotificacionController::class, 'index']);
        Route::patch('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasComoLeidas']);
        Route::patch('/notificaciones/{id_notificacion}/leer', [NotificacionController::class, 'marcarComoLeida']);

        // APIs 27 y 28: rutas administrativas.
        Route::prefix('admin')
            ->middleware('administrador')
            ->group(function () {

                // API 27: administración de usuarios.
                Route::get('/usuarios', [AdminUsuarioController::class, 'index']);
                Route::get('/usuarios/{id_usuario}', [AdminUsuarioController::class, 'show']);
                Route::patch('/usuarios/{id_usuario}/suspender', [AdminUsuarioController::class, 'suspender']);
                Route::patch('/usuarios/{id_usuario}/reactivar', [AdminUsuarioController::class, 'reactivar']);

                // API 28: administración de solicitudes de habilitación.
                Route::get('/solicitudes-habilitacion', [AdminSolicitudHabilitacionController::class, 'index']);
                Route::get('/solicitudes-habilitacion/{id_solicitud}', [AdminSolicitudHabilitacionController::class, 'show'])
                    ->whereNumber('id_solicitud');
                Route::patch('/solicitudes-habilitacion/{id_solicitud}/revisar', [AdminSolicitudHabilitacionController::class, 'revisar'])
                    ->whereNumber('id_solicitud');
                Route::patch('/solicitudes-habilitacion/{id_solicitud}/aprobar', [AdminSolicitudHabilitacionController::class, 'aprobar'])
                    ->whereNumber('id_solicitud');
                Route::patch('/solicitudes-habilitacion/{id_solicitud}/rechazar', [AdminSolicitudHabilitacionController::class, 'rechazar'])
                    ->whereNumber('id_solicitud');

                // API 29: administración de reportes.
                Route::get('/reportes', [AdminReporteController::class, 'index']);
                Route::get('/reportes/{id_reporte}', [AdminReporteController::class, 'show'])
                    ->whereNumber('id_reporte');
                Route::patch('/reportes/{id_reporte}/revisar', [AdminReporteController::class, 'revisar'])
                    ->whereNumber('id_reporte');
                Route::patch('/reportes/{id_reporte}/resolver', [AdminReporteController::class, 'resolver'])
                    ->whereNumber('id_reporte');

                // API 30: administración de oficios.
                Route::get('/oficios', [AdminOficioController::class, 'index']);
                Route::post('/oficios', [AdminOficioController::class, 'store']);
                Route::patch('/oficios/{id_oficio}', [AdminOficioController::class, 'update'])
                    ->whereNumber('id_oficio');
                Route::patch('/oficios/{id_oficio}/desactivar', [AdminOficioController::class, 'desactivar'])
                    ->whereNumber('id_oficio');
            });
    });
});
