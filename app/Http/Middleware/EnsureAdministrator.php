<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        // La ruta está protegida previamente por auth:sanctum.
        if (! $usuario || ! $usuario->activo) {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta está inactiva.',
                'error_code' => 'ACCOUNT_INACTIVE',
                'errors' => null,
            ], 403);
        }

        if (! $usuario->es_administrador) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para acceder a este recurso.',
                'error_code' => 'FORBIDDEN',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
