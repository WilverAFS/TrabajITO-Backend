<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id('id_notificacion');
            
            $table->foreignId('id_usuario')
                ->constrained(
                    table: 'users',
                    column: 'id_usuario'
                )
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            
            $table->smallInteger('id_tipo_notificacion');
            $table->foreign('id_tipo_notificacion')
                ->references('id_tipo_notificacion')
                ->on('tipos_notificacion')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('titulo', 150);
            $table->text('mensaje');
            $table->boolean('leida')->default(false);
            $table->timestamp('fecha_creacion')->useCurrent();

            //Optimiza la consulta de notificaciones no leidas de un usuario
            $table->index(
                ['id_usuario', 'leida', 'fecha_creacion'],
                'notificaciones_usuario_leida_fecha_index'
            );

            $table->index(
                'id_tipo_notificacion',
                'notificaciones_tipo_index'
            );

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
