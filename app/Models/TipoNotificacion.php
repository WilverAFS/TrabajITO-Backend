<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoNotificacion extends Model
{
    protected $table = 'tipos_notificacion';

    protected $primaryKey = 'id_tipo_notificacion';

    public $timestamps = false;

    protected $fillable = [
        'tipo_notificacion',
    ];

    public function notificaciones(): HasMany
    {
        return $this->hasMany(
            Notificacion::class,
            'id_tipo_notificacion',
            'id_tipo_notificacion'
        );
    }
}
