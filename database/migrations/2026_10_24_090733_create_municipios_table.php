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
        Schema::create('municipios', function (Blueprint $table) {
            $table->id('id_municipio');
            $table->foreignId('id_estado')
                ->constrained(
                    table: 'estados',
                    column: 'id_estado'
                )
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('municipio', 150);
            $table->boolean('activo')->default(true);

            //Evita que un minicipio se registre mas de una vez dentro del mismo estado
            $table->unique(
                ['id_estado', 'municipio'],
                'municipios_estado_municipio_unique'
            );

            //Facilita consultar los municipios habilitados pertenecientes a un estado
            $table->index(
                ['id_estado', 'activo'],
                'municipios_estado_activo_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
