<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reporte extends Model
{
    protected $table = 'reportes';

    protected $primaryKey = 'id_reporte';

    public $timestamps = false;

    protected $fillable = [
        'id_denunciante',
        'tipo_objetivo',
        'id_objetivo',
        'id_admin_moderador',
        'motivo',
        'descripcion',
        'id_estado_reporte',
        'resolucion',
        'fecha_creacion',
        'fecha_resolucion',
    ];

    protected function casts(): array
    {
        return [
            'id_objetivo' => 'integer',
            'fecha_creacion' => 'datetime',
            'fecha_resolucion' => 'datetime',
        ];
    }

    public function denunciante(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_denunciante',
            'id_usuario'
        );
    }

    public function moderador(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_admin_moderador',
            'id_usuario'
        );
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(
            EstadoReporte::class,
            'id_estado_reporte',
            'id_estado_reporte'
        );
    }
}
