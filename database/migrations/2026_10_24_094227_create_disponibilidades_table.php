<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disponibilidades', function (Blueprint $table) {
            $table->id('id_disponibilidad');

            $table->foreignId('id_trabajador')
                ->constrained(
                    table: 'perfiles_profesionales',
                    column: 'id_trabajador'
                )
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /**
             * Convención:
             * 1 = lunes
             * 2 = martes
             * 3 = miércoles
             * 4 = jueves
             * 5 = viernes
             * 6 = sábado
             * 7 = domingo
             */
            $table->smallInteger('dia_semana');

            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->boolean('activo')->default(true);

            //Evita registrar exactamente el mismo intervalo mas de una vez para un trabajador
            $table->unique(
                [
                    'id_trabajador',
                    'dia_semana',
                    'hora_inicio',
                    'hora_fin',
                ],
                'disponibilidad_intervalo_unique'
            );

            //Facilita consultar disponibilidad por trabajador, dia y estado.
            $table->index(
                ['id_trabajador', 'dia_semana', 'activo'],
                'disponibilidad_trabajador_dia_index'
            );
        });

        //Restricciones especificas de integridad para PostgreSQL
        DB::statement(
            'ALTER TABLE disponibilidades
            ADD CONSTRAINT disponibilidades_dia_semana_check
            CHECK (dia_semana BETWEEN 1 AND 7)'
        );

        DB::statement(
            'ALTER TABLE disponibilidades
            ADD CONSTRAINT disponibilidades_horas_check
            CHECK (hora_fin > hora_inicio)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disponibilidades');
    }
};
