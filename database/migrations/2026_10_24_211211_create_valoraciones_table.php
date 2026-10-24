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
        Schema::create('valoraciones', function (Blueprint $table) {
            $table->id('id_valoracion');

            //Una publicacion solo puede recibir una valoracion
            $table->foreignId('id_publicacion')
                ->unique()
                ->constrained(
                    table: 'publicaciones',
                    column: 'id_publicacion'
                )
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            
            //Usuario cliente que registra la valoracion
            $table->foreignId('id_cliente')
                ->constrained(
                    table: 'users',
                    column: 'id_usuario'
                )
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            
            $table->smallInteger('calificacion');
            $table->text('comentario')->nullable();
            $table->timestamp('fecha_valoracion')->useCurrent();

            $table->index(
                ['id_cliente', 'fecha_valoracion'],
                'valoraciones_cliente_fecha_index'
            );
        });

        DB::statement(
            'ALTER TABLE valoraciones
            ADD CONSTRAINT valoraciones_calificacion_check
            CHECK (calificacion BETWEEN 1 AND 5)'
        );
    }
    /**
     * En Laravel se implementa la verificacion para que 
     * solamente el cliente que participo en la contratacion
     * pueda valorar el trabajo.
     */
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valoraciones');
    }
};
