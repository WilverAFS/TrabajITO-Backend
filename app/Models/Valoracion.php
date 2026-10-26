<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Valoracion extends Model
{
    protected $table = 'valoraciones';

    protected $primaryKey = 'id_valoracion';

    public $timestamps = false;

    protected $fillable = [
        'id_publicacion',
        'id_cliente',
        'calificacion',
        'comentario',
        'fecha_valoracion',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'calificacion' => 'integer',
            'fecha_valoracion' => 'datetime',
        ];
    }

    /**
     * Scope para consultar valoraciones de una publicación.
     */
    public function scopeDePublicacion(
        Builder $query,
        int $idPublicacion
    ): Builder {
        return $query->where('id_publicacion', $idPublicacion);
    }

    /**
     * Scope para filtrar valoraciones por calificación.
     */
    public function scopeConCalificacion(
        Builder $query,
        int $calificacion
    ): Builder {
        return $query->where('calificacion', $calificacion);
    }

    /**
     * Relación con la publicación valorada.
     */
    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(
            Publicacion::class,
            'id_publicacion',
            'id_publicacion'
        );
    }

    /**
     * Relación con el cliente que realizó la valoración.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_cliente',
            'id_usuario'
        );
    }
}
