<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    //Ejecuta los seeders de la aplicación.
    public function run(): void
    {
        $this->call([
            
            //Catálogos geográficos.
            EstadoSeeder::class,
            MunicipioSeeder::class,
            
            //Catálogo profesional.
            OficioSeeder::class,

            //Catálogos de estados y tipos.
            EstadoSolicitudSeeder::class,
            EstadoContratacionSeeder::class,
            TipoPublicacionSeeder::class,
            EstadoReporteSeeder::class,
            TipoNotificacionSeeder::class,

            //Usuarios y datos relacionados
            UsuarioSeeder::class,
            PerfilProfesionalSeeder::class,
            DatosPruebaSeeder::class,
        ]);
    }
}