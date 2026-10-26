<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'nombre',
    'apellido',
    'correo',
    'contrasena_hash',
    'telefono',
])]
#[Hidden([
    'contrasena_hash',
    'remember_token',
])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // 1. Tabla
    protected $table = 'users';

    // 2. Clave primaria
    protected $primaryKey = 'id_usuario';

    // 3. No utiliza created_at ni updated_at
    public $timestamps = false;

    // Contraseña personalizada para autenticación
    public function getAuthPasswordName(): string
    {
        return 'contrasena_hash';
    }

    // Correo personalizado para recuperación de contraseña
    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    // 6. Conversiones de tipos
    protected function casts(): array
    {
        return [
            'es_administrador' => 'boolean',
            'activo' => 'boolean',
            'fecha_registro' => 'datetime',
            'contrasena_hash' => 'hashed',
        ];
    }

    // 7. Relaciones Eloquent

    // Un usuario puede tener un perfil profesional
    public function perfilProfesional(): HasOne
    {
        return $this->hasOne(
            PerfilProfesional::class,
            'id_usuario',
            'id_usuario'
        );
    }

    // Solicitudes que revisa como administrador
    public function solicitudesHabilitacionRevisadas(): HasMany
    {
        return $this->hasMany(
            SolicitudHabilitacion::class,
            'id_admin_revisor',
            'id_usuario'
        );
    }

    // Contrataciones que realiza como cliente
    public function contratacionesSolicitadas(): HasMany
    {
        return $this->hasMany(
            Contratacion::class,
            'id_cliente',
            'id_usuario'
        );
    }

    // Valoraciones que realiza como cliente
    public function valoracionesRealizadas(): HasMany
    {
        return $this->hasMany(
            Valoracion::class,
            'id_cliente',
            'id_usuario'
        );
    }

    // Reportes que presenta
    public function reportesPresentados(): HasMany
    {
        return $this->hasMany(
            Reporte::class,
            'id_denunciante',
            'id_usuario'
        );
    }

    // Reportes que atiende como administrador
    public function reportesModerados(): HasMany
    {
        return $this->hasMany(
            Reporte::class,
            'id_admin_moderador',
            'id_usuario'
        );
    }

    // Notificaciones que recibe
    public function notificaciones(): HasMany
    {
        return $this->hasMany(
            Notificacion::class,
            'id_usuario',
            'id_usuario'
        );
    }

    // 8. Scopes de consulta reutilizables

    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }

    #[Scope]
    protected function administradores(Builder $query): void
    {
        $query->where('es_administrador', true);
    }

    #[Scope]
    protected function conCorreo(
        Builder $query,
        string $correo
    ): void {
        $query->where('correo', $correo);
    }
}
