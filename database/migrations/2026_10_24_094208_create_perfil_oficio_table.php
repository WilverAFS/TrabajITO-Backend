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
        Schema::create('perfil_oficio', function (Blueprint $table) {
            $table->foreignId('id_trabajador')
                ->constrained(
                    table: 'perfiles_profesionales',
                    column: 'id_trabajador'
                )
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->foreignId('id_oficio')
                ->constrained(
                    table: 'oficios',
                    column: 'id_oficio'
                )
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            
            //La combinacion de trabajador y oficio constituye la clave primaria de la tabla pivote
            $table->primary(
                ['id_trabajador', 'id_oficio'],
                'perfil_oficio_primary'
            );

            //Facilita buscar todos los trabajadores asociados con determinado oficio
            $table->index(
                'id_oficio',
                'perfil_oficio_oficio_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perfil_oficio');
    }
};
