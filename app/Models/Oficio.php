<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Oficio extends Model
{
    // 1. Tabla correspondiente
    protected $table = 'oficios';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_oficio';

    // 3. Sin created_at ni updated_at
    public $timestamps = false;

    // 4. Campos asignables
    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    // 5. Sin campos sensibles
    protected $hidden = [];

    // 6. Conversiones de tipos
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    // 7. Relaciones Eloquent

    // Un oficio puede pertenecer a varios perfiles profesionales
    public function perfilesProfesionales(): BelongsToMany
    {
        return $this->belongsToMany(
            PerfilProfesional::class,
            'perfil_oficio',
            'id_oficio',
            'id_trabajador',
            'id_oficio',
            'id_trabajador'
        )->withTimestamps();
    }

    // 8. Scopes reutilizables

    // Consultar solamente los oficios activos
    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }

    // Consultar un oficio por su nombre
    #[Scope]
    protected function porNombre(
        Builder $query,
        string $nombre
    ): void {
        $query->where('nombre', $nombre);
    }
}
