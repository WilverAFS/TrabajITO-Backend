<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoContratacion extends Model
{
    protected $table = 'estados_contratacion';

    protected $primaryKey = 'id_estado_contratacion';

    public $timestamps = false;

    protected $fillable = [
        'estado_contratacion',
    ];

    public function contrataciones(): HasMany
    {
        return $this->hasMany(
            Contratacion::class,
            'id_estado_contratacion',
            'id_estado_contratacion'
        );
    }
}