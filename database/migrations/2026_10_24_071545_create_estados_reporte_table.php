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
        Schema::create('estados_reporte', function (Blueprint $table) {
            $table->smallIncrements('id_estado_reporte');
            $table->string('estado_reporte', 50)->unique();
        });
    }
    /**
     * Valores iniciales en el Seeder:
     * 1. Pendiente
     * 2. En revisión
     * 3. Concluido
     */

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_reporte');
    }
};
