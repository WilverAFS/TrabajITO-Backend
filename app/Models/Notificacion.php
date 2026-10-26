<?php

namespace App\Models;

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

    protected function casts(): array
    {
        return [
            'leida' => 'boolean',
            'fecha_creacion' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_usuario',
            'id_usuario'
        );
    }

    public function tipoNotificacion(): BelongsTo
    {
        return $this->belongsTo(
            TipoNotificacion::class,
            'id_tipo_notificacion',
            'id_tipo_notificacion'
        );
    }
}
