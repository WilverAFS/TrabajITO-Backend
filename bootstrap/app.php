<?php

use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
    })->create();
