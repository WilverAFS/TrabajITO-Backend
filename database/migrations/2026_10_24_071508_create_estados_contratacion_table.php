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
        Schema::create('estados_contratacion', function (Blueprint $table) {
            $table->smallIncrements('id_estado_contratacion');
            $table->string('estado_contratacion', 50)->unique();
        });
    }
    /**
     * En los Seeder se ingresan los valores iniciales:
     * 1. Pendiente
     * 2. Aceptada
     * 3. Rechazada
     * 4. En proceso
     * 5. Finalizada
     * 6. Cancelada
     */


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_contratacion');
    }
};
