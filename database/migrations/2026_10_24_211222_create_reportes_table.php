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
        Schema::create('reportes', function (Blueprint $table) {
            $table->id('id_reporte');

            //Usuario que genera el reporte
            $table->foreignId('id_denunciante')
                ->constrained(
                    table: 'users',
                    column: 'id_usuario'
                )
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            
            /**
             * Valores admitidos
             * PUBLICACION
             * PERFIL
             */
            $table->string('tipo_objetivo', 30);

            /**
             * Identificador de la publicacion o perfil.
             * 
             * No puede tener una clave foranea convencional
             * porque puede apunta a dos tablas diferentes.
             */
            $table->unsignedBigInteger('id_objetivo');

            //Administrador encargado de eresolver el reporte
            $table->foreignId('id_admin_moderador')
                ->nullable()
                ->constrained(
                    table: 'users',
                    column: 'id_usuario'
                )
                ->cascadeOnUpdate()
                ->nullOnDelete();
            
            $table->string('motivo', 150);
            $table->text('descripcion');

            $table->smallInteger('id_estado_reporte');
            $table->foreign('id_estado_reporte')
                ->references('id_estado_reporte')
                ->on('estados_reporte')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            
            $table->text('resolucion')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_resolucion')->nullable();

            $table->index(
                ['id_estado_reporte', 'fecha_creacion'],
                'reportes_estado_fecha_index'
            );

            $table->index(
                'id_admin_moderador',
                'reportes_admin_index'
            );
            
        });

        DB::statement(
            "ALTER TABLE reportes
            ADD CONSTRAINT reportes_tipo_objetivo_check
            CHECK (tipo_objetivo IN ('PUBLICACION', 'PERFIL'))"
        );

        DB::statement(
            'ALTER TABLE reportes
            ADD CONSTRAINT reportes_fechas_check
            CHECK (
                fecha_resolucion IS NULL
                OR fecha_resolucion >= fecha_creacion
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
