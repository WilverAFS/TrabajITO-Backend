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
        Schema::create('tipos_notificacion', function (Blueprint $table) {
            $table->smallIncrements('id_tipo_notificacion');
            $table->string('tipo_notificacion', 50)->unique();
        });
    }
    /**
     * Valores iniciales en el Seeder:
     * 1. Solicitud
     * 2. Contratación
     * 3. Publicación
     * 4. Sistema
     * 5. Reporte
     */

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_notificacion');
    }
};
