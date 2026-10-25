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
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nombre', 100);
            $table->string('apellido', 150);
            $table->string('correo', 255)->unique();
            $table->string('contrasena_hash');
            $table->string('telefono', 10)
                ->unique()
                ->nullable();
            $table->boolean('es_administrador')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamp('fecha_registro')->useCurrent();
            /**
             * Token utilizado por laravel para
             * mantener sesiones persistentes
             */
            $table->rememberToken();
            
        });

        /**
         * Tabla utilizada para la recuperacion de contraseñas
         * 
         * Se mantiene "email" porque Laracel utiliza esta
         * columna por convencion en el broker de contraseñas
         */
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        
        /**
         * Tabla para manejar sesiones en la base de datos
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index(); //No se define FK hasta decidir implementar la autenticacion para React con sesiones mediante Laravel Sanctum o autenticacion basada en tokens.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
