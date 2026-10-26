<?php

namespace App\Models;

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

    public function publicaciones(): HasMany
    {
        return $this->hasMany(
            Publicacion::class,
            'id_tipo_publicacion',
            'id_tipo_publicacion'
        );
    }
}
