<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contratacion extends Model
{
    protected $table = 'contrataciones';

    protected $primaryKey = 'id_contratacion';

    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'id_trabajador',
        'id_estado_contratacion',
        'descripcion_servicio',
        'fecha_solicitud',
        'fecha_respuesta',
        'fecha_finalizacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'datetime',
            'fecha_respuesta' => 'datetime',
            'fecha_finalizacion' => 'datetime',
        ];
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(
            EstadoContratacion::class,
            'id_estado_contratacion',
            'id_estado_contratacion'
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

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(
            PerfilProfesional::class,
            'id_trabajador',
            'id_trabajador'
        );
    }
}
