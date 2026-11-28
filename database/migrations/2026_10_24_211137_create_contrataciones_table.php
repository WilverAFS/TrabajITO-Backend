<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contrataciones', function (Blueprint $table) {
            $table->id('id_contratacion');

            // Usuario que solicita el servicio
            $table->foreignId('id_cliente')
                ->constrained(
                    table: 'users',
                    column: 'id_usuario'
                )
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Perfil profesional que recibe la solicitud
            $table->foreignId('id_trabajador')
                ->constrained(
                    table: 'perfiles_profesionales',
                    column: 'id_trabajador'
                )
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // El catálogo utiliza smallint como PK
            $table->smallInteger('id_estado_contratacion');

            $table->foreign('id_estado_contratacion')
                ->references('id_estado_contratacion')
                ->on('estados_contratacion')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->text('descripcion_servicio');
            $table->timestamp('fecha_solicitud')->useCurrent();

            // Fecha en la que el trabajador acepta o rechaza
            $table->timestamp('fecha_respuesta')->nullable();

            // Solo se registra cuando el trabajo se completa
            $table->timestamp('fecha_finalizacion')->nullable();

            $table->index(
                ['id_cliente', 'fecha_solicitud'],
                'constrataciones_cliente_fecha_index'
            );

            $table->index(
                ['id_trabajador', 'id_estado_contratacion'],
                'contrataciones_trabajador_estado_index'
            );

            $table->index(
                ['id_estado_contratacion', 'fecha_solicitud'],
                'contrataciones_estado_fecha_index'
            );
        });

        /**
         * Un usuario no puede contratarse a sí mismo.
         *
         * Esta regla se valida en la lógica de negocio de Laravel.
         */
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE contrataciones
                ADD CONSTRAINT contrataciones_fecha_check
                CHECK (
                    fecha_finalizacion IS NULL
                    OR fecha_finalizacion >= fecha_solicitud
                )'
            );

            DB::statement(
                'ALTER TABLE contrataciones
                ADD CONSTRAINT contrataciones_respuesta_check
                CHECK (
                    fecha_respuesta IS NULL
                    OR fecha_respuesta >= fecha_solicitud
                )'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrataciones');
    }
};
