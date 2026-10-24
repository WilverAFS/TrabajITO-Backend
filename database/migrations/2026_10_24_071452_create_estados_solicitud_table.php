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
        Schema::create('estados_solicitud', function (Blueprint $table) {
            $table->smallIncrements('id_estado_solicitud');
            $table->string('estado_solicitud', 50)->unique();
        });
    }
    /**
     * En los Seeder se ingresan los valores iniciales:
     * 1. Pendiente
     * 2. En revisión
     * 3. Aceptada
     * 4. Rechazada
     */

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_solicitud');
    }
};
