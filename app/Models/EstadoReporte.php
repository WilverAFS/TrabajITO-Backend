<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoReporte extends Model
{
    protected $table = 'estados_reporte';

    protected $primaryKey = 'id_estado_reporte';

    public $timestamps = false;

    protected $fillable = [
        'estado_reporte',
    ];

    protected $hidden = [];

    /**
     * Scope para buscar un estado de reporte por nombre exacto.
     */
    public function scopeConNombre(
        Builder $query,
        string $nombre
    ): Builder {
        return $query->where('estado_reporte', $nombre);
    }

    /**
     * Scope para buscar estados de reporte por coincidencia de nombre.
     */
    public function scopeBuscarNombre(
        Builder $query,
        string $nombre
    ): Builder {
        return $query->where(
            'estado_reporte',
            'like',
            '%' . $nombre . '%'
        );
    }

    /**
     * Relación con los reportes que tienen este estado.
     */
    public function reportes(): HasMany
    {
        return $this->hasMany(
            Reporte::class,
            'id_estado_reporte',
            'id_estado_reporte'
        );
    }
}
