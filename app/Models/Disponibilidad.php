<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disponibilidad extends Model
{
    // 1. Tabla correspondiente
    protected $table = 'disponibilidades';

    // 2. Clave primaria personalizada
    protected $primaryKey = 'id_disponibilidad';

    // 3. Sin created_at ni updated_at
    public $timestamps = false;

    // 4. Campos asignables
    protected $fillable = [
        'id_trabajador',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'activo',
    ];

    // 5. Sin campos sensibles
    protected $hidden = [];

    // 6. Conversiones de tipos
    protected function casts(): array
    {
        return [
            'id_trabajador' => 'integer',
            'dia_semana' => 'integer',
            'activo' => 'boolean',
        ];
    }

    // 7. Relaciones Eloquent

    // Cada disponibilidad pertenece a un perfil profesional
    public function perfilProfesional(): BelongsTo
    {
        return $this->belongsTo(
            PerfilProfesional::class,
            'id_trabajador',
            'id_trabajador'
        );
    }

    // 8. Scopes de consulta reutilizables

    // Consultar horarios activos
    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }

    // Consultar horarios de un trabajador
    #[Scope]
    protected function porTrabajador(
        Builder $query,
        int $idTrabajador
    ): void {
        $query->where('id_trabajador', $idTrabajador);
    }

    // Consultar horarios de un dia especifico
    #[Scope]
    protected function porDia(
        Builder $query,
        int $diaSemana
    ): void {
        $query->where('dia_semana', $diaSemana);
    }
}
