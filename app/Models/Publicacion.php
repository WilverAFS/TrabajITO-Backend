<?php

namespace App\Models;

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

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'fecha_publicacion' => 'datetime',
        ];
    }

    public function contratacion(): BelongsTo
    {
        return $this->belongsTo(
            Contratacion::class,
            'id_contratacion',
            'id_contratacion'
        );
    }

    public function tipoPublicacion(): BelongsTo
    {
        return $this->belongsTo(
            TipoPublicacion::class,
            'id_tipo_publicacion',
            'id_tipo_publicacion'
        );
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(
            Evidencia::class,
            'id_publicacion',
            'id_publicacion'
        );
    }

    public function valoracion(): HasOne
    {
        return $this->hasOne(
            Valoracion::class,
            'id_publicacion',
            'id_publicacion'
        );
    }
}
