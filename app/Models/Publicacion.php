<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Publicacion extends Model
{
    protected $table = 'publicaciones';

    protected $primaryKey = 'id_publicacion';

    public $timestamps = false;

    protected $fillable = [
        'id_contratacion',
        'id_tipo_publicacion',
        'titulo',
        'descripcion',
        'visible',
        'fecha_publicacion',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'fecha_publicacion' => 'datetime',
        ];
    }

    /**
     * Scope para consultar únicamente las publicaciones visibles.
     */
    public function scopeVisibles(Builder $query): Builder
    {
        return $query->where('visible', true);
    }

    /**
     * Scope para consultar publicaciones por tipo.
     */
    public function scopeDeTipo(Builder $query, int $idTipo): Builder
    {
        return $query->where('id_tipo_publicacion', $idTipo);
    }

    /**
     * Relación con la contratación asociada.
     */
    public function contratacion(): BelongsTo
    {
        return $this->belongsTo(
            Contratacion::class,
            'id_contratacion',
            'id_contratacion'
        );
    }

    /**
     * Relación con el tipo de publicación.
     */
    public function tipoPublicacion(): BelongsTo
    {
        return $this->belongsTo(
            TipoPublicacion::class,
            'id_tipo_publicacion',
            'id_tipo_publicacion'
        );
    }

    /**
     * Relación con las evidencias de la publicación.
     */
    public function evidencias(): HasMany
    {
        return $this->hasMany(
            Evidencia::class,
            'id_publicacion',
            'id_publicacion'
        );
    }

    /**
     * Relación con la valoración de la publicación.
     */
    public function valoracion(): HasOne
    {
        return $this->hasOne(
            Valoracion::class,
            'id_publicacion',
            'id_publicacion'
        );
    }
}
