<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    // 1. Tabla correspondiente
    protected $table = 'municipios';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_municipio';

    // 3. Sin created_at ni updated_at
    public $timestamps = false;

    // 4. Campos asignables
    protected $fillable = [
        'id_estado',
        'municipio',
        'activo',
    ];

    // 5. Sin campos sensibles
    protected $hidden = [];

    // 6. Conversiones de tipos
    protected function casts(): array
    {
        return [
            'id_estado' => 'integer',
            'activo' => 'boolean',
        ];
    }

    // 7. Relaciones Eloquent

    // Cada municipio pertenece a un estado
    public function estado(): BelongsTo
    {
        return $this->belongsTo(
            Estado::class,
            'id_estado',
            'id_estado'
        );
    }

    // Un municipio puede tener varios trabajadores
    public function perfilesProfesionales(): HasMany
    {
        return $this->hasMany(
            PerfilProfesional::class,
            'id_municipio',
            'id_municipio'
        );
    }

    // 8. Scopes reutilizables

    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }

    #[Scope]
    protected function porEstado(
        Builder $query,
        int $idEstado
    ): void {
        $query->where('id_estado', $idEstado);
    }

    #[Scope]
    protected function porNombre(
        Builder $query,
        string $nombre
    ): void {
        $query->where('municipio', $nombre);
    }
}