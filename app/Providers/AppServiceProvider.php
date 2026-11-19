<?php

namespace App\Providers;

use App\Models\Contratacion;
use App\Models\Reporte;
use App\Models\SolicitudHabilitacion;
use App\Models\User;
use App\Observers\ContratacionObserver;
use App\Observers\ReporteObserver;
use App\Observers\SolicitudHabilitacionObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Observers existentes
        Contratacion::observe(ContratacionObserver::class);
        SolicitudHabilitacion::observe(SolicitudHabilitacionObserver::class);
        Reporte::observe(ReporteObserver::class);

        // Observer para correos de usuarios
        User::observe(UserObserver::class);

        // Enlace de recuperación dirigido al Frontend
        ResetPassword::createUrlUsing(
            function (User $user, string $token): string {
                $frontend = rtrim(
                    (string) config('services.trabajito.frontend_url'),
                    '/'
                );

                $parametros = http_build_query(
                    [
                        'token' => $token,
                        'correo' => $user->correo,
                    ],
                    '',
                    '&',
                    PHP_QUERY_RFC3986
                );

                return "{$frontend}/restablecer-contrasena?{$parametros}";
            }
        );
    }
}
