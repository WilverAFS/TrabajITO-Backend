<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    /**
     * Impide que una cuenta suspendida utilice rutas autenticadas.
     *
     * Este middleware se ejecuta después de auth:sanctum.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->activo) {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta está inactiva.',
                'error_code' => 'ACCOUNT_INACTIVE',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
