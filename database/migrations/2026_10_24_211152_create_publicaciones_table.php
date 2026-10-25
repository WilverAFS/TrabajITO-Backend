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
        Schema::create('publicaciones', function (Blueprint $table) {
            $table->id('id_publicacion');
            
            /**
             * UNIQUE asegura una publicacion como maximo por contratacion
             * La regla: 
             *  - Solo se puede publicar una contratación finalizada
             * se validará en Laravel, porque requiere consultar el estado actual de la contratación.
             */
            $table->foreignId('id_contratacion')
                ->unique()
                ->constrained(
                    table: 'contrataciones',
                    column: 'id_contratacion'
                )
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            
            //Catalogo con clave SMALLINT
            $table->smallInteger('id_tipo_publicacion');

            $table->foreign('id_tipo_publicacion')
                ->references('id_tipo_publicacion')
                ->on('tipos_publicacion')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('titulo', 150);
            $table->text('descripcion');
            $table->boolean('visible')->default(true);
            $table->timestamp('fecha_publicacion')->useCurrent();

            $table->index(
                ['visible', 'fecha_publicacion'],
                'publicaciones_visible_fecha_index'
            );

            $table->index(
                'id_tipo_publicacion',
                'publicaciones_tipo_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publicaciones');
    }
};
