<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $primaryKey = 'id_notificacion';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_tipo_notificacion',
        'titulo',
        'mensaje',
        'leida',
        'fecha_creacion',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'leida' => 'boolean',
            'fecha_creacion' => 'datetime',
        ];
    }

    /**
     * Scope para obtener las notificaciones no leídas.
     */
    public function scopeNoLeidas(Builder $query): Builder
    {
        return $query->where('leida', false);
    }

    /**
     * Scope para filtrar las notificaciones de un usuario.
     */
    public function scopeDeUsuario(
        Builder $query,
        int $idUsuario
    ): Builder {
        return $query->where('id_usuario', $idUsuario);
    }

    /**
     * Relación con el usuario que recibe la notificación.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_usuario',
            'id_usuario'
        );
    }

    /**
     * Relación con el tipo de notificación.
     */
    public function tipoNotificacion(): BelongsTo
    {
        return $this->belongsTo(
            TipoNotificacion::class,
            'id_tipo_notificacion',
            'id_tipo_notificacion'
        );
    }
}
