<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;

class Estado extends Model
{
    // 1. Tabla correspondiente
    protected $table = 'estados';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_estado';

    // 3. No utiliza created_at ni updated_at
    public $timestamps = false;

    // 4. Campos asignables
    protected $fillable = [
        'estado',
        'activo',
    ];

    // 5. No contiene campos sensibles que deban ocultarse
    protected $hidden = [];

    // 6. Conversiones de tipos
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    // 7. Relaciones Eloquent

    // Un estado puede tener varios municipios
    public function municipios(): HasMany
    {
        return $this->hasMany(
            Municipio::class,
            'id_estado',
            'id_estado'
        );
    }

    // 8. Scopes reutilizables

    // Consultar solamente estados activos
    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }

    // Consultar un estado por su nombre
    #[Scope]
    protected function porNombre(
        Builder $query,
        string $nombre
    ): void {
        $query->where('estado', $nombre);
    }
}
