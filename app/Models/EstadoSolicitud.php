<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoSolicitud extends Model
{
    // 1. Tabla correspondiente
    protected $table = 'estados_solicitud';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_estado_solicitud';

    // 3. Sin created_at ni updated_at
    public $timestamps = false;

    // 4. Campos asignables
    protected $fillable = [
        'estado_solicitud',
    ];

    // 5. Sin campos sensibles
    protected $hidden = [];

    // 6. Conversiones de tipos
    protected function casts(): array
    {
        return [
            'id_estado_solicitud' => 'integer',
        ];
    }

    // 7. Relaciones Eloquent

    // Un estado puede pertenecer a muchas solicitudes
    public function solicitudesHabilitacion(): HasMany
    {
        return $this->hasMany(
            SolicitudHabilitacion::class,
            'id_estado_solicitud',
            'id_estado_solicitud'
        );
    }

    // 8. Scopes de consulta reutilizables

    // Estado Pendiente (ID 1)
    #[Scope]
    protected function pendientes(Builder $query): void
    {
        $query->where('id_estado_solicitud', 1);
    }

    // Estado Aceptada (ID 3)
    #[Scope]
    protected function aceptadas(Builder $query): void
    {
        $query->where('id_estado_solicitud', 3);
    }

    // Buscar un estado por su nombre
    #[Scope]
    protected function porNombre(
        Builder $query,
        string $nombre
    ): void {
        $query->where('estado_solicitud', $nombre);
    }

    // Estados que tienen solicitudes asociadas
    #[Scope]
    protected function conSolicitudes(Builder $query): void
    {
        $query->has('solicitudesHabilitacion');
    }
}