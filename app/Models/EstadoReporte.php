<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoReporte extends Model
{
    protected $table = 'estados_reporte';

    protected $primaryKey = 'id_estado_reporte';

    public $timestamps = false;

    protected $fillable = [
        'estado_reporte',
    ];

    public function reportes(): HasMany
    {
        return $this->hasMany(
            Reporte::class,
            'id_estado_reporte',
            'id_estado_reporte'
        );
    }
}
