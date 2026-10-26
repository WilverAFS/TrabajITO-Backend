<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoNotificacion extends Model
{
    protected $table = 'tipos_notificacion';

    protected $primaryKey = 'id_tipo_notificacion';

    public $timestamps = false;

    protected $fillable = [
        'tipo_notificacion',
    ];

    protected $hidden = [];

    /**
     * Scope para buscar un tipo de notificación por nombre exacto.
     */
    public function scopeConNombre(
        Builder $query,
        string $nombre
    ): Builder {
        return $query->where('tipo_notificacion', $nombre);
    }

    /**
     * Scope para buscar tipos de notificación por coincidencia de nombre.
     */
    public function scopeBuscarNombre(
        Builder $query,
        string $nombre
    ): Builder {
        return $query->where(
            'tipo_notificacion',
            'like',
            '%' . $nombre . '%'
        );
    }

    /**
     * Relación con las notificaciones de este tipo.
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(
            Notificacion::class,
            'id_tipo_notificacion',
            'id_tipo_notificacion'
        );
    }
}
