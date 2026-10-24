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
        Schema::create('solicitudes_habilitacion', function (Blueprint $table) {
            $table->id('id_solicitud');

            //Perfil profesional que solicita la habilitacion
            $table->foreignId('id_trabajador')
                ->constrained(
                    table: 'perfiles_profesionales',
                    column: 'id_trabajador'
                )
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            
            //Administrador que revisa la solicitud
            $table->foreignId('id_admin_revisor')
                ->nullable()
                ->constrained(
                    table: 'users',
                    column: 'id_usuario'
                )
                ->cascadeOnUpdate()
                ->nullOnDelete();
            
            //Estado actual de la solicitud
            $table->foreignId('id_estado_solicitud')
                ->constrained(
                    table: 'estados_solicitud',
                    column: 'id_estado_solicitud'
                )
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            
            $table->text('comentario_revision')->nullable();
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamp('fecha_revision')->nullable();

            //Consulta administrativa de solicitudes segun su estado y fecha
            $table->index(
                ['id_estado_solicitud', 'fecha_solicitud'],
                'solicitudes_estado_fecha_index'
            );

            //Consulta del historial de solicitudes pertenecientes a un perfil
            $table->index(
                ['id_trabajador', 'fecha_solicitud'],
                'solicitudes_trabajador_fecha_index'
            );

            //Consulta de solicitudes atendidas por determinado administrador
            $table->index(
                'id_admin_revisor',
                'solicitudes_admin_revisor_index'
            );
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_habilitacion');
    }
};
