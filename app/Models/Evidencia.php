<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidencia extends Model
{
    protected $table = 'evidencias';

    protected $primaryKey = 'id_evidencia';

    public $timestamps = false;

    protected $fillable = [
        'id_publicacion',
        'tipo_archivo',
        'ruta_archivo',
        'fecha_carga',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'fecha_carga' => 'datetime',
        ];
    }

    /**
     * Scope para consultar evidencias de una publicación.
     */
    public function scopeDePublicacion(
        Builder $query,
        int $idPublicacion
    ): Builder {
        return $query->where('id_publicacion', $idPublicacion);
    }

    /**
     * Scope para consultar evidencias por tipo de archivo.
     */
    public function scopeDeTipoArchivo(
        Builder $query,
        string $tipo
    ): Builder {
        return $query->where('tipo_archivo', strtoupper($tipo));
    }

    /**
     * Relación con la publicación a la que pertenece la evidencia.
     */
    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(
            Publicacion::class,
            'id_publicacion',
            'id_publicacion'
        );
    }
}
