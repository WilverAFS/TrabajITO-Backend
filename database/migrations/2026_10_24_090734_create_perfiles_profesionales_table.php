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
        Schema::create('perfiles_profesionales', function (Blueprint $table) {
            $table->id('id_trabajador');
            $table->foreignId('id_usuario')
                ->unique()
                ->constrained(
                    table: 'users',
                    column: 'id_usuario'
                )
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_municipio')
                ->constrained(
                    table: 'municipios',
                    column: 'id_municipio'
                )
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('descripcion');
            $table->timestamp('fecha_registro')->useCurrent();
            $table->boolean('activo')->default(false); //Inactivo hasta que un administrado lo apruebe
            $table->index(
                'activo',
                'perfiles_activo_index'
            );
            $table->index(
                ['id_municipio', 'activo'],
                'perfiles_muninipio_activo_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perfiles_profesionales');
    }
};
