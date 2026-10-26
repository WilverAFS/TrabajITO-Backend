<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudHabilitacion extends Model
{
    // 1. Tabla correspondiente
    protected $table = 'solicitudes_habilitacion';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_solicitud';

    // 3. Sin created_at ni updated_at
    public $timestamps = false;

    // Estado inicial: Pendiente
    protected $attributes = [
        'id_estado_solicitud' => 1,
    ];

    // 4. Campos asignables
    // Los datos de revisión se administran por separado.
    protected $fillable = [
        'id_trabajador',
    ];

    // 5. Sin campos ocultos
    protected $hidden = [];

    // 6. Conversiones de tipos
    protected function casts(): array
    {
        return [
            'id_trabajador' => 'integer',
            'id_admin_revisor' => 'integer',
            'id_estado_solicitud' => 'integer',
            'fecha_solicitud' => 'datetime',
            'fecha_revision' => 'datetime',
        ];
    }

    // 7. Relaciones Eloquent

    // Perfil profesional que realiza la solicitud
    public function perfilProfesional(): BelongsTo
    {
        return $this->belongsTo(
            PerfilProfesional::class,
            'id_trabajador',
            'id_trabajador'
        );
    }

    // Administrador encargado de revisar
    public function administradorRevisor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_admin_revisor',
            'id_usuario'
        );
    }

    // Estado actual de la solicitud
    public function estadoSolicitud(): BelongsTo
    {
        return $this->belongsTo(
            EstadoSolicitud::class,
            'id_estado_solicitud',
            'id_estado_solicitud'
        );
    }

    // 8. Scopes reutilizables

    // Solicitudes pendientes
    #[Scope]
    protected function pendientes(Builder $query): void
    {
        $query->where('id_estado_solicitud', 1);
    }

    // Consultar solicitudes según su estado
    #[Scope]
    protected function porEstado(
        Builder $query,
        int $idEstado
    ): void {
        $query->where('id_estado_solicitud', $idEstado);
    }

    // Solicitudes pertenecientes a un trabajador
    #[Scope]
    protected function porTrabajador(
        Builder $query,
        int $idTrabajador
    ): void {
        $query->where('id_trabajador', $idTrabajador);
    }

    // Solicitudes revisadas por un administrador
    #[Scope]
    protected function revisadasPor(
        Builder $query,
        int $idAdministrador
    ): void {
        $query->where('id_admin_revisor', $idAdministrador);
    }
}