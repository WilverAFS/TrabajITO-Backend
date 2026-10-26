<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerfilProfesional extends Model
{
    // 1. Tabla correspondiente
    protected $table = 'perfiles_profesionales';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_trabajador';

    // 3. Sin timestamps estándar
    public $timestamps = false;

    // 4. Campos asignables
    protected $fillable = [
        'id_usuario',
        'id_municipio',
        'descripcion',
    ];

    // 5. Sin campos sensibles que deban ocultarse
    protected $hidden = [];

    // 6. Conversiones de tipos
    protected function casts(): array
    {
        return [
            'id_usuario' => 'integer',
            'id_municipio' => 'integer',
            'fecha_registro' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    // 7. Relaciones Eloquent

    // Cada perfil pertenece a un usuario
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_usuario',
            'id_usuario'
        );
    }

    // Cada perfil pertenece a un municipio
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(
            Municipio::class,
            'id_municipio',
            'id_municipio'
        );
    }

    // Un trabajador puede ofrecer varios oficios
    public function oficios(): BelongsToMany
    {
        return $this->belongsToMany(
            Oficio::class,
            'perfil_oficio',
            'id_trabajador',
            'id_oficio',
            'id_trabajador',
            'id_oficio'
        )->withTimestamps();
    }

    // Disponibilidades del trabajador
    public function disponibilidades(): HasMany
    {
        return $this->hasMany(
            Disponibilidad::class,
            'id_trabajador',
            'id_trabajador'
        );
    }

    // Certificados del trabajador
    public function certificados(): HasMany
    {
        return $this->hasMany(
            Certificado::class,
            'id_trabajador',
            'id_trabajador'
        );
    }

    // Solicitudes de habilitación del perfil
    public function solicitudesHabilitacion(): HasMany
    {
        return $this->hasMany(
            SolicitudHabilitacion::class,
            'id_trabajador',
            'id_trabajador'
        );
    }

    // Contrataciones recibidas por el trabajador
    public function contrataciones(): HasMany
    {
        return $this->hasMany(
            Contratacion::class,
            'id_trabajador',
            'id_trabajador'
        );
    }

    // 8. Scopes reutilizables

    // Perfiles habilitados
    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }

    // Perfiles de un municipio
    #[Scope]
    protected function porMunicipio(
        Builder $query,
        int $idMunicipio
    ): void {
        $query->where('id_municipio', $idMunicipio);
    }

    // Perfil asociado a un usuario
    #[Scope]
    protected function porUsuario(
        Builder $query,
        int $idUsuario
    ): void {
        $query->where('id_usuario', $idUsuario);
    }

    // Trabajadores que ofrecen un oficio específico
    #[Scope]
    protected function conOficio(
        Builder $query,
        int $idOficio
    ): void {
        $query->whereHas('oficios', function (Builder $consulta) use ($idOficio) {
            $consulta->where('oficios.id_oficio', $idOficio);
        });
    }
}