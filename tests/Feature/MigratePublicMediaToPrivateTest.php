<?php

namespace Tests\Feature;

use App\Models\Certificado;
use App\Models\Estado;
use App\Models\Evidencia;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\Publicacion;
use App\Models\TipoPublicacion;
use App\Models\Contratacion;
use App\Models\EstadoContratacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigratePublicMediaToPrivateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    private function crearUsuario(): User
    {
        return User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Migración',
            'correo' => uniqid('migracion-', true) . '@example.test',
            'contrasena_hash' => 'Prueba123!',
        ]);
    }

    private function crearPerfil(User $usuario): PerfilProfesional
    {
        $estado = Estado::create([
            'estado' => 'Estado ' . uniqid(),
            'activo' => true,
        ]);

        $municipio = Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Municipio ' . uniqid(),
            'activo' => true,
        ]);

        return $usuario->perfilProfesional()->create([
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil de migración.',
        ]);
    }

    /**
     * Crea una publicación y su evidencia utilizando un perfil que ya existe.
     * Recibir el perfil evita crear un segundo perfil para el mismo usuario.
     */
    private function crearEvidencia(PerfilProfesional $perfil): Evidencia
    {
        $cliente = $this->crearUsuario();

        foreach ([
            'Pendiente',
            'Aceptada',
            'Rechazada',
            'En proceso',
            'Finalizada',
            'Cancelada',
        ] as $estado) {
            EstadoContratacion::create([
                'estado_contratacion' => $estado,
            ]);
        }

        $tipo = TipoPublicacion::create([
            'tipo_publicacion' => 'Proyecto',
        ]);

        $contratacion = Contratacion::create([
            'id_cliente' => $cliente->id_usuario,
            'id_trabajador' => $perfil->id_trabajador,
            'id_estado_contratacion' => 5,
            'descripcion_servicio' => 'Servicio finalizado.',
            'fecha_solicitud' => now()->subDays(2),
            'fecha_respuesta' => now()->subDay(),
            'fecha_finalizacion' => now(),
        ]);

        $publicacion = Publicacion::create([
            'id_contratacion' => $contratacion->id_contratacion,
            'id_tipo_publicacion' => $tipo->id_tipo_publicacion,
            'titulo' => 'Publicación de migración',
            'descripcion' => 'Descripción de publicación para migración.',
            'visible' => true,
            'fecha_publicacion' => now(),
        ]);

        return Evidencia::create([
            'id_publicacion' => $publicacion->id_publicacion,
            'tipo_archivo' => 'IMAGEN',
            'ruta_archivo' => 'publicaciones/imagen-migrar.jpg',
            'fecha_carga' => now(),
        ]);
    }

    public function test_migra_certificados_y_evidencias_y_elimina_copias_publicas(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $certificado = $perfil->certificados()->create([
            'nombre' => 'Certificado',
            'institucion_emisora' => 'Institución',
            'fecha_emision' => '2025-05-15',
            'fecha_vencimiento' => null,
            'ruta_documento' => 'certificados/antiguo.pdf',
        ]);

        $evidencia = $this->crearEvidencia($perfil);

        Storage::disk('public')->put(
            $certificado->ruta_documento,
            'CONTENIDO-CERTIFICADO'
        );
        Storage::disk('public')->put(
            $evidencia->ruta_archivo,
            'CONTENIDO-EVIDENCIA'
        );

        $this->artisan('media:migrate-public-to-private')
            ->assertExitCode(0);

        Storage::disk('local')->assertExists($certificado->ruta_documento);
        Storage::disk('local')->assertExists($evidencia->ruta_archivo);

        Storage::disk('public')->assertMissing($certificado->ruta_documento);
        Storage::disk('public')->assertMissing($evidencia->ruta_archivo);

        $this->assertDatabaseHas('certificados', [
            'id_certificado' => $certificado->id_certificado,
            'ruta_documento' => 'certificados/antiguo.pdf',
        ]);

        $this->assertDatabaseHas('evidencias', [
            'id_evidencia' => $evidencia->id_evidencia,
            'ruta_archivo' => 'publicaciones/imagen-migrar.jpg',
        ]);
    }

    public function test_simulacion_no_mueve_ni_elimina_archivos(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $certificado = $perfil->certificados()->create([
            'nombre' => 'Certificado',
            'institucion_emisora' => 'Institución',
            'fecha_emision' => '2025-05-15',
            'fecha_vencimiento' => null,
            'ruta_documento' => 'certificados/simulacion.pdf',
        ]);

        Storage::disk('public')->put(
            $certificado->ruta_documento,
            'CONTENIDO-SIMULACION'
        );

        $this->artisan('media:migrate-public-to-private --dry-run')
            ->assertExitCode(0);

        Storage::disk('public')->assertExists($certificado->ruta_documento);
        Storage::disk('local')->assertMissing($certificado->ruta_documento);
    }

    public function test_no_sobrescribe_un_archivo_privado_distinto(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $certificado = $perfil->certificados()->create([
            'nombre' => 'Certificado',
            'institucion_emisora' => 'Institución',
            'fecha_emision' => '2025-05-15',
            'fecha_vencimiento' => null,
            'ruta_documento' => 'certificados/conflicto.pdf',
        ]);

        Storage::disk('public')->put(
            $certificado->ruta_documento,
            'CONTENIDO-PUBLICO'
        );
        Storage::disk('local')->put(
            $certificado->ruta_documento,
            'CONTENIDO-PRIVADO-DIFERENTE'
        );

        $this->artisan('media:migrate-public-to-private')
            ->assertExitCode(1);

        Storage::disk('public')->assertExists($certificado->ruta_documento);
        Storage::disk('local')->assertExists($certificado->ruta_documento);
        $this->assertSame(
            'CONTENIDO-PRIVADO-DIFERENTE',
            Storage::disk('local')->get($certificado->ruta_documento)
        );
    }
}
