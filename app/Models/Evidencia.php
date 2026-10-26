<?php

namespace App\Models;

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

    protected function casts(): array
    {
        return [
            'fecha_carga' => 'datetime',
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
}
