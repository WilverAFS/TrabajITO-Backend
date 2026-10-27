<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoContratacion extends Model
{
    protected $table = 'estados_contratacion';

    protected $primaryKey = 'id_estado_contratacion';

    public $timestamps = false;

    protected $fillable = [
        'estado_contratacion',
    ];

    protected $hidden = [];

    /**
     * Scope para buscar estados por su nombre.
     */
    public function scopeConNombre(
        Builder $query,
        string $nombre
    ): Builder {
        return $query->where('estado_contratacion', $nombre);
    }

    /**
     * Scope para buscar estados que tengan un nombre similar.
     */
    public function scopeBuscarNombre(
        Builder $query,
        string $nombre
    ): Builder {
        return $query->where(
            'estado_contratacion',
            'like',
            '%' . $nombre . '%'
        );
    }

    /**
     * Relación con las contrataciones que tienen este estado.
     */
    public function contrataciones(): HasMany
    {
        return $this->hasMany(
            Contratacion::class,
            'id_estado_contratacion',
            'id_estado_contratacion'
        );
    }
}
