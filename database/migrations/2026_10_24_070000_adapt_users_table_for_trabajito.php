
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('id', 'id_usuario');
            $table->renameColumn('name', 'nombre');
            $table->renameColumn('email', 'correo');
            $table->renameColumn('password', 'contrasena_hash');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
            $table->dropTimestamps();

            $table->string('apellido', 150)->nullable();
            $table->string('telefono', 10)->nullable()->unique();
            $table->boolean('es_administrador')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamp('fecha_registro')->useCurrent();
        });
    }

    public function down(): void
    {
        // No revertir automáticamente estos cambios.
    }
};
