<?php

namespace App\Models;

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

    protected function casts(): array
    {
        return [
            'calificacion' => 'integer',
            'fecha_valoracion' => 'datetime',
        ];
    }

    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(
            Publicacion::class,
            'id_publicacion',
            'id_publicacion'
        );
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_cliente',
            'id_usuario'
        );
    }
}
