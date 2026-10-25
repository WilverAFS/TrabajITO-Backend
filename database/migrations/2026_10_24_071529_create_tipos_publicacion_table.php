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
        Schema::create('tipos_publicacion', function (Blueprint $table) {
            $table->smallIncrements('id_tipo_publicacion');
            $table->string('tipo_publicacion', 50)->unique();
        });
    }
    /**
     * Valores iniciales definidos en el Seeder:
     * 1. Diagnóstico
     * 2. Instalación
     * 3. Reparación
     * 4. Mantenimiento
     * 5. Construcción
     */

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_publicacion');
    }
};
