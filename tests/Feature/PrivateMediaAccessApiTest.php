<?php

namespace Tests\Feature;

use App\Models\Certificado;
use App\Models\Contratacion;
use App\Models\Estado;
use App\Models\EstadoContratacion;
use App\Models\Evidencia;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\Publicacion;
use App\Models\TipoPublicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateMediaAccessApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function crearUsuario(bool $administrador = false): User
    {
        $usuario = User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => uniqid('media-', true) . '@example.test',
            'telefono' => null,
            'contrasena_hash' => 'Prueba123!',
        ]);

        if ($administrador) {
            $usuario->es_administrador = true;
            $usuario->save();
        }

        return $usuario->fresh();
    }

    private function crearPerfil(User $usuario): PerfilProfesional
    {
        $sufijo = bin2hex(random_bytes(4));

        $estado = Estado::create([
            'estado' => 'Estado ' . $sufijo,
            'activo' => true,
        ]);

        $municipio = Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Municipio ' . $sufijo,
            'activo' => true,
        ]);

        return $usuario->perfilProfesional()->create([
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil profesional para pruebas de medios.',
        ]);
    }

    private function crearToken(User $usuario): string
    {
        return $usuario->createToken('Pruebas medios privados')
            ->plainTextToken;
    }

    private function crearCertificado(
        PerfilProfesional $perfil,
        string $ruta
    ): Certificado {
        return $perfil->certificados()->create([
            'nombre' => 'Certificado de prueba',
            'institucion_emisora' => 'Institución de prueba',
            'fecha_emision' => '2025-05-15',
            'fecha_vencimiento' => null,
            'ruta_documento' => $ruta,
        ]);
    }

    private function crearPublicacion(User $trabajador): Publicacion
    {
        $cliente = $this->crearUsuario();
        $perfil = $this->crearPerfil($trabajador);

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
            'descripcion_servicio' => 'Servicio finalizado para prueba de medios.',
            'fecha_solicitud' => now()->subDays(2),
            'fecha_respuesta' => now()->subDay(),
            'fecha_finalizacion' => now(),
        ]);

        return Publicacion::create([
            'id_contratacion' => $contratacion->id_contratacion,
            'id_tipo_publicacion' => $tipo->id_tipo_publicacion,
            'titulo' => 'Publicación para prueba de archivos',
            'descripcion' => 'Descripción de prueba suficientemente extensa.',
            'visible' => true,
            'fecha_publicacion' => now(),
        ]);
    }

    public function test_certificado_no_puede_abrirse_sin_autenticacion(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);
        $certificado = $this->crearCertificado(
            $perfil,
            'certificados/privado.pdf'
        );

        Storage::disk('local')->put(
            $certificado->ruta_documento,
            'CONTENIDO-CERTIFICADO-PRIVADO'
        );

        $this->get(
            "/api/v1/certificados/{$certificado->id_certificado}/documento"
        )->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_titular_puede_abrir_su_certificado_privado(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);
        $certificado = $this->crearCertificado(
            $perfil,
            'certificados/titular.pdf'
        );

        Storage::disk('local')->put(
            $certificado->ruta_documento,
            'CONTENIDO-CERTIFICADO-TITULAR'
        );

        $respuesta = $this->withToken($this->crearToken($usuario))
            ->get("/api/v1/certificados/{$certificado->id_certificado}/documento")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        /*
         * Cache-Control es una lista de directivas. Su orden no forma parte
         * del significado, por lo que la prueba compara el conjunto completo.
         */
        $directivasCache = array_map(
            'trim',
            explode(',', (string) $respuesta->headers->get('Cache-Control'))
        );

        $this->assertEqualsCanonicalizing(
            ['private', 'no-store', 'max-age=0'],
            $directivasCache
        );
    }

    public function test_administrador_puede_abrir_certificado_de_otro_trabajador(): void
    {
        $trabajador = $this->crearUsuario();
        $perfil = $this->crearPerfil($trabajador);
        $certificado = $this->crearCertificado(
            $perfil,
            'certificados/admin.pdf'
        );

        Storage::disk('local')->put(
            $certificado->ruta_documento,
            'CONTENIDO-CERTIFICADO-ADMIN'
        );

        $administrador = $this->crearUsuario(true);

        $this->withToken($this->crearToken($administrador))
            ->get("/api/v1/certificados/{$certificado->id_certificado}/documento")
            ->assertOk();
    }

    public function test_usuario_ajeno_no_puede_abrir_certificado(): void
    {
        $titular = $this->crearUsuario();
        $perfil = $this->crearPerfil($titular);
        $certificado = $this->crearCertificado(
            $perfil,
            'certificados/ajeno.pdf'
        );

        Storage::disk('local')->put(
            $certificado->ruta_documento,
            'CONTENIDO-CERTIFICADO-AJENO'
        );

        $otroUsuario = $this->crearUsuario();

        $this->withToken($this->crearToken($otroUsuario))
            ->get("/api/v1/certificados/{$certificado->id_certificado}/documento")
            ->assertForbidden()
            ->assertJsonPath('error_code', 'CERTIFICATE_ACCESS_DENIED');
    }

    public function test_certificado_devuelve_url_de_endpoint_autorizado(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);
        $certificado = $this->crearCertificado(
            $perfil,
            'certificados/url.pdf'
        );

        $respuesta = $this->withToken($this->crearToken($usuario))
            ->getJson('/api/v1/me/perfil/certificados')
            ->assertOk();

        $url = $respuesta->json('data.0.documento_url');

        $this->assertIsString($url);
        $this->assertStringContainsString(
            "/api/v1/certificados/{$certificado->id_certificado}/documento",
            $url
        );
        $this->assertStringNotContainsString('/storage/', $url);
    }

    public function test_evidencia_de_publicacion_visible_puede_abrirse_sin_sesion(): void
    {
        $trabajador = $this->crearUsuario();
        $publicacion = $this->crearPublicacion($trabajador);

        $evidencia = Evidencia::create([
            'id_publicacion' => $publicacion->id_publicacion,
            'tipo_archivo' => 'IMAGEN',
            'ruta_archivo' => 'publicaciones/visible.jpg',
            'fecha_carga' => now(),
        ]);

        Storage::disk('local')->put(
            $evidencia->ruta_archivo,
            'CONTENIDO-IMAGEN-VISIBLE'
        );

        $this->get(
            "/api/v1/evidencias/{$evidencia->id_evidencia}/archivo"
        )->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_evidencia_de_publicacion_oculta_no_es_publica(): void
    {
        $trabajador = $this->crearUsuario();
        $publicacion = $this->crearPublicacion($trabajador);
        $publicacion->visible = false;
        $publicacion->save();

        $evidencia = Evidencia::create([
            'id_publicacion' => $publicacion->id_publicacion,
            'tipo_archivo' => 'IMAGEN',
            'ruta_archivo' => 'publicaciones/oculta.jpg',
            'fecha_carga' => now(),
        ]);

        Storage::disk('local')->put(
            $evidencia->ruta_archivo,
            'CONTENIDO-IMAGEN-OCULTA'
        );

        $this->get(
            "/api/v1/evidencias/{$evidencia->id_evidencia}/archivo"
        )->assertNotFound()
            ->assertJsonPath('error_code', 'PUBLICATION_HIDDEN');
    }

    public function test_propietario_puede_abrir_evidencia_de_publicacion_oculta(): void
    {
        $trabajador = $this->crearUsuario();
        $publicacion = $this->crearPublicacion($trabajador);
        $publicacion->visible = false;
        $publicacion->save();

        $evidencia = Evidencia::create([
            'id_publicacion' => $publicacion->id_publicacion,
            'tipo_archivo' => 'IMAGEN',
            'ruta_archivo' => 'publicaciones/oculta-propietario.jpg',
            'fecha_carga' => now(),
        ]);

        Storage::disk('local')->put(
            $evidencia->ruta_archivo,
            'CONTENIDO-PROPIETARIO'
        );

        $this->withToken($this->crearToken($trabajador))
            ->get("/api/v1/evidencias/{$evidencia->id_evidencia}/archivo")
            ->assertOk();
    }

    public function test_administrador_puede_abrir_evidencia_de_publicacion_oculta(): void
    {
        $trabajador = $this->crearUsuario();
        $publicacion = $this->crearPublicacion($trabajador);
        $publicacion->visible = false;
        $publicacion->save();

        $evidencia = Evidencia::create([
            'id_publicacion' => $publicacion->id_publicacion,
            'tipo_archivo' => 'IMAGEN',
            'ruta_archivo' => 'publicaciones/oculta-admin.jpg',
            'fecha_carga' => now(),
        ]);

        Storage::disk('local')->put(
            $evidencia->ruta_archivo,
            'CONTENIDO-ADMIN'
        );

        $administrador = $this->crearUsuario(true);

        $this->withToken($this->crearToken($administrador))
            ->get("/api/v1/evidencias/{$evidencia->id_evidencia}/archivo")
            ->assertOk();
    }

    public function test_usuario_ajeno_no_puede_abrir_evidencia_oculta(): void
    {
        $trabajador = $this->crearUsuario();
        $publicacion = $this->crearPublicacion($trabajador);
        $publicacion->visible = false;
        $publicacion->save();

        $evidencia = Evidencia::create([
            'id_publicacion' => $publicacion->id_publicacion,
            'tipo_archivo' => 'IMAGEN',
            'ruta_archivo' => 'publicaciones/oculta-ajeno.jpg',
            'fecha_carga' => now(),
        ]);

        Storage::disk('local')->put(
            $evidencia->ruta_archivo,
            'CONTENIDO-AJENO'
        );

        $otroUsuario = $this->crearUsuario();

        $this->withToken($this->crearToken($otroUsuario))
            ->get("/api/v1/evidencias/{$evidencia->id_evidencia}/archivo")
            ->assertNotFound()
            ->assertJsonPath('error_code', 'PUBLICATION_HIDDEN');
    }

    public function test_cuenta_inactiva_no_puede_abrir_evidencia_oculta(): void
    {
        $trabajador = $this->crearUsuario();
        $publicacion = $this->crearPublicacion($trabajador);
        $publicacion->visible = false;
        $publicacion->save();

        $evidencia = Evidencia::create([
            'id_publicacion' => $publicacion->id_publicacion,
            'tipo_archivo' => 'IMAGEN',
            'ruta_archivo' => 'publicaciones/oculta-inactivo.jpg',
            'fecha_carga' => now(),
        ]);

        Storage::disk('local')->put(
            $evidencia->ruta_archivo,
            'CONTENIDO-INACTIVO'
        );

        $token = $this->crearToken($trabajador);
        $trabajador->activo = false;
        $trabajador->save();

        $this->withToken($token)
            ->get("/api/v1/evidencias/{$evidencia->id_evidencia}/archivo")
            ->assertForbidden()
            ->assertJsonPath('error_code', 'ACCOUNT_INACTIVE');
    }

    public function test_recurso_de_evidencia_devuelve_endpoint_controlado(): void
    {
        $trabajador = $this->crearUsuario();
        $publicacion = $this->crearPublicacion($trabajador);

        $evidencia = Evidencia::create([
            'id_publicacion' => $publicacion->id_publicacion,
            'tipo_archivo' => 'IMAGEN',
            'ruta_archivo' => 'publicaciones/recurso.jpg',
            'fecha_carga' => now(),
        ]);

        $respuesta = $this->getJson(
            "/api/v1/publicaciones/{$publicacion->id_publicacion}"
        )->assertOk();

        $url = $respuesta->json('data.evidencias.0.archivo_url');

        $this->assertIsString($url);
        $this->assertStringContainsString(
            "/api/v1/evidencias/{$evidencia->id_evidencia}/archivo",
            $url
        );
        $this->assertStringNotContainsString('/storage/', $url);
    }
}
