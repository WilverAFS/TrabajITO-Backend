<?php

namespace App\Providers;

use App\Models\Contratacion;
use App\Models\Reporte;
use App\Models\SolicitudHabilitacion;
use App\Observers\ContratacionObserver;
use App\Observers\ReporteObserver;
use App\Observers\SolicitudHabilitacionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Contratacion::observe(ContratacionObserver::class);
        SolicitudHabilitacion::observe(SolicitudHabilitacionObserver::class);
        Reporte::observe(ReporteObserver::class);
    }
}
