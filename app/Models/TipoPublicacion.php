<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoPublicacion extends Model
{
    protected $table = 'tipos_publicacion';

    protected $primaryKey = 'id_tipo_publicacion';

    public $timestamps = false;

    protected $fillable = [
        'tipo_publicacion',
    ];

    protected $hidden = [];

    /**
     * Scope para buscar un tipo de publicación por su nombre exacto.
     */
    public function scopeConNombre(
        Builder $query,
        string $nombre
    ): Builder {
        return $query->where('tipo_publicacion', $nombre);
    }

    /**
     * Scope para buscar tipos de publicación por coincidencia de nombre.
     */
    public function scopeBuscarNombre(
        Builder $query,
        string $nombre
    ): Builder {
        return $query->where(
            'tipo_publicacion',
            'like',
            '%' . $nombre . '%'
        );
    }

    /**
     * Relación con las publicaciones de este tipo.
     */
    public function publicaciones(): HasMany
    {
        return $this->hasMany(
            Publicacion::class,
            'id_tipo_publicacion',
            'id_tipo_publicacion'
        );
    }
}
