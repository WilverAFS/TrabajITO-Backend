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
        Schema::create('evidencias', function (Blueprint $table) {
            $table->id('id_evidencia');

            $table->foreignId('id_publicacion')
                ->constrained(
                    table: 'publicaciones',
                    column: 'id_publicacion'
                )
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /**
             * Valores permitidos:
             * IMAGEN
             * VIDEO
             */
            $table->string('tipo_archivo', 20);

            //Ruta relativa, indentificados del archivo o clave utilizada por el almacenamiento.
            $table->string('ruta_archivo', 500);

            $table->timestamp('fecha_carga')->useCurrent();

            $table->index(
                ['id_publicacion', 'tipo_archivo'],
                'evidencias_publicacion_tipo_index'
            );
        });

        DB::statement(
            "ALTER TABLE evidencias
            ADD CONSTRAINT evidencias_tipo_archivo_check
            CHECK (tipo_archivo IN ('IMAGEN', 'VIDEO'))"
        );
        /**
         * Las sigueintes reglas deberan validarse depues con Laravel:
         * 
         * Maximo 10 imagnes por publicacion
         * Maximo 3 videos por publicacion
         * Imagen de hasta 10MB
         * Video de hasta 90 segundos
         * Extensiones y tipos MIME permitidos.
         * 
         */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidencias');
    }
};
