<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'administrador' => EnsureAdministrator::class,
            'cuenta.activa' => EnsureActiveAccount::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (
            AuthenticationException $exception,
            Request $request
        ) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión para acceder a este recurso.',
                'error_code' => 'UNAUTHENTICATED',
                'errors' => null,
            ], 401);
        });

        $exceptions->render(function (
            AuthorizationException $exception,
            Request $request
        ) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción.',
                'error_code' => 'FORBIDDEN',
                'errors' => null,
            ], 403);
        });

        $exceptions->render(function (
            AccessDeniedHttpException $exception,
            Request $request
        ) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción.',
                'error_code' => 'FORBIDDEN',
                'errors' => null,
            ], 403);
        });

        $exceptions->render(function (
            ValidationException $exception,
            Request $request
        ) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Los datos proporcionados no son válidos.',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (
            NotFoundHttpException $exception,
            Request $request
        ) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Recurso no encontrado.',
                'error_code' => 'NOT_FOUND',
                'errors' => null,
            ], 404);
        });

        $exceptions->render(function (
            MethodNotAllowedHttpException $exception,
            Request $request
        ) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Método HTTP no permitido.',
                'error_code' => 'METHOD_NOT_ALLOWED',
                'errors' => null,
            ], 405);
        });

        $exceptions->render(function (
            TooManyRequestsHttpException $exception,
            Request $request
        ) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Ha excedido el número permitido de solicitudes. Intente nuevamente más tarde.',
                'error_code' => 'TOO_MANY_REQUESTS',
                'errors' => null,
            ], 429, $exception->getHeaders());
        });

        $exceptions->render(function (
            HttpExceptionInterface $exception,
            Request $request
        ) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Error en la solicitud.',
                'error_code' => 'HTTP_ERROR',
                'errors' => null,
            ], $exception->getStatusCode(), $exception->getHeaders());
        });

        $exceptions->render(function (
            \Throwable $exception,
            Request $request
        ) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error interno del servidor.',
                'error_code' => 'INTERNAL_SERVER_ERROR',
                'errors' => null,
            ], 500);
        });
    })
    ->create();
