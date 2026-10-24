<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id('id_certificado');

            $table->foreignId('id_trabajador')
                ->constrained(
                    table: 'perfiles_profesionales',
                    column: 'id_trabajador'
                )
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('nombre', 150);
            $table->string('institucion_emisora', 150);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable(); //Algunos certificados no tienen vencimiento
            $table->string('ruta_documento', 500);
            $table->boolean('verificado')->default(false);

            $table->index(
                ['id_trabajador', 'verificado'],
                'certificado_trabajador_verificado_index'
            );
        });


        //Cuando exista fecha de vencimineot, esta no puede ser anterior a la fecha de emision
        DB::statement(
            'ALTER TABLE certificados
            ADD CONSTRAINT certificados_fecha_check
            CHECK (
                fecha_vencimiento IS NULL 
                OR fecha_vencimiento >= fecha_emision
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};
