<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificado extends Model
{
    // 1. Tabla correspondiente
    protected $table = 'certificados';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_certificado';

    // 3. Sin timestamps estándar
    public $timestamps = false;

    // 4. Campos asignables
    // La verificación corresponde a un proceso administrativo.
    protected $fillable = [
        'id_trabajador',
        'nombre',
        'institucion_emisora',
        'fecha_emision',
        'fecha_vencimiento',
        'ruta_documento',
    ];

    // 5. Campos ocultos al serializar el modelo
    protected $hidden = [
        'ruta_documento',
    ];

    // 6. Conversiones de tipos
    protected function casts(): array
    {
        return [
            'id_trabajador' => 'integer',
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'verificado' => 'boolean',
        ];
    }

    // 7. Relaciones Eloquent

    // Cada certificado pertenece a un perfil profesional
    public function perfilProfesional(): BelongsTo
    {
        return $this->belongsTo(
            PerfilProfesional::class,
            'id_trabajador',
            'id_trabajador'
        );
    }

    // 8. Scopes de consulta reutilizables

    // Certificados verificados
    #[Scope]
    protected function verificados(Builder $query): void
    {
        $query->where('verificado', true);
    }

    // Certificados pertenecientes a un trabajador
    #[Scope]
    protected function porTrabajador(
        Builder $query,
        int $idTrabajador
    ): void {
        $query->where('id_trabajador', $idTrabajador);
    }

    // Certificados emitidos que todavía están vigentes
    #[Scope]
    protected function vigentes(Builder $query): void
    {
        $hoy = today()->toDateString();

        $query
            ->whereDate('fecha_emision', '<=', $hoy)
            ->where(function (Builder $consulta) use ($hoy) {
                $consulta
                    ->whereNull('fecha_vencimiento')
                    ->orWhereDate('fecha_vencimiento', '>=', $hoy);
            });
    }
}