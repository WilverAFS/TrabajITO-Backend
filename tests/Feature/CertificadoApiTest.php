<?php

namespace Tests\Feature;

use App\Models\Certificado;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificadoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function crearUsuario(): User
    {
        return User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => 'certificado.' . bin2hex(random_bytes(6)) . '@example.test',
            'telefono' => null,
            'contrasena_hash' => 'Prueba123!',
        ]);
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
            'descripcion' => 'Perfil profesional de prueba.',
        ]);
    }

    private function crearCertificado(
        PerfilProfesional $perfil,
        array $datos = []
    ): Certificado {
        return $perfil->certificados()->create(
            array_merge([
                'nombre' => 'Certificado de electricidad',
                'institucion_emisora' => 'Institución de prueba',
                'fecha_emision' => '2025-05-15',
                'fecha_vencimiento' => null,
                'ruta_documento' => 'certificados/prueba.pdf',
            ], $datos)
        );
    }

    private function crearToken(User $usuario): string
    {
        return $usuario->createToken('Pruebas automatizadas')
            ->plainTextToken;
    }

    private function enviarRegistro(
        string $token,
        array $datos
    ) {
        return $this->withHeaders([
            'Accept' => 'application/json',
        ])->withToken($token)->post(
            '/api/v1/me/perfil/certificados',
            $datos
        );
    }

    // 1. Consultar exclusivamente los certificados propios.
    public function test_consultar_certificados_propios(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $this->crearCertificado($perfil, [
            'nombre' => 'Certificado antiguo',
            'fecha_emision' => '2024-05-15',
        ]);

        $this->crearCertificado($perfil, [
            'nombre' => 'Certificado reciente',
            'fecha_emision' => '2025-05-15',
        ]);

        // Crear certificado de otro trabajador.
        $otroUsuario = $this->crearUsuario();
        $otroPerfil = $this->crearPerfil($otroUsuario);

        $this->crearCertificado($otroPerfil, [
            'nombre' => 'Certificado ajeno',
        ]);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->getJson('/api/v1/me/perfil/certificados');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Certificados obtenidos correctamente.'
            )
            ->assertJsonCount(2, 'data')
            ->assertJsonPath(
                'data.0.nombre',
                'Certificado reciente'
            )
            ->assertJsonPath(
                'data.1.nombre',
                'Certificado antiguo'
            )
            ->assertJsonMissing([
                'nombre' => 'Certificado ajeno',
            ]);
    }

    // 2. Todas las operaciones requieren autenticación.
    public function test_certificados_requieren_autenticacion(): void
    {
        $this->getJson('/api/v1/me/perfil/certificados')
            ->assertUnauthorized();

        $this->postJson('/api/v1/me/perfil/certificados', [])
            ->assertUnauthorized();

        $this->deleteJson('/api/v1/me/perfil/certificados/1')
            ->assertUnauthorized();
    }

    // 3. Rechazar operaciones cuando el usuario no tiene perfil.
    public function test_rechazar_operaciones_sin_perfil(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->crearToken($usuario);

        $this->withToken($token)
            ->getJson('/api/v1/me/perfil/certificados')
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'PROFILE_NOT_FOUND'
            );

        $this->enviarRegistro($token, [
            'nombre' => 'Certificado de prueba',
            'institucion_emisora' => 'Institución de prueba',
            'fecha_emision' => '2025-05-15',
            'documento' => UploadedFile::fake()->create(
                'certificado.pdf',
                100,
                'application/pdf'
            ),
        ])->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'PROFILE_NOT_FOUND'
            );

        $this->withToken($token)
            ->deleteJson('/api/v1/me/perfil/certificados/1')
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'PROFILE_NOT_FOUND'
            );

        $this->assertDatabaseCount('certificados', 0);

        Storage::disk('public')
            ->assertDirectoryEmpty('certificados');
    }

    // 4. Registrar correctamente un certificado PDF.
    public function test_registrar_certificado_correctamente(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $respuesta = $this->enviarRegistro($token, [
            'nombre' => 'Instalaciones eléctricas residenciales',
            'institucion_emisora' => 'Institución de capacitación',
            'fecha_emision' => '2025-05-15',
            'fecha_vencimiento' => null,
            'documento' => UploadedFile::fake()->create(
                'certificado.pdf',
                100,
                'application/pdf'
            ),
        ]);

        $respuesta->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Certificado registrado correctamente.'
            )
            ->assertJsonPath(
                'data.nombre',
                'Instalaciones eléctricas residenciales'
            )
            ->assertJsonPath(
                'data.institucion_emisora',
                'Institución de capacitación'
            )
            ->assertJsonPath(
                'data.fecha_emision',
                '2025-05-15'
            )
            ->assertJsonPath(
                'data.fecha_vencimiento',
                null
            )
            ->assertJsonPath('data.verificado', false);

        $certificado = Certificado::firstOrFail();

        $this->assertSame(
            $perfil->id_trabajador,
            $certificado->id_trabajador
        );

        $this->assertDatabaseHas('certificados', [
            'id_certificado' => $certificado->id_certificado,
            'id_trabajador' => $perfil->id_trabajador,
            'verificado' => false,
        ]);

        Storage::disk('public')
            ->assertExists($certificado->ruta_documento);

        $this->assertStringStartsWith(
            '/storage/certificados/',
            $respuesta->json('data.documento_url')
        );

        $respuesta->assertJsonMissingPath(
            'data.ruta_documento'
        );
    }

    // 5. Aceptar documentos JPG y PNG.
    public function test_aceptar_documentos_jpg_y_png(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $archivos = [
            UploadedFile::fake()->create(
                'certificado.jpg',
                100,
                'image/jpeg'
            ),

            UploadedFile::fake()->create(
                'certificado.png',
                100,
                'image/png'
            ),
        ];

        foreach ($archivos as $archivo) {

            $respuesta = $this->enviarRegistro($token, [
                'nombre' => 'Certificado de prueba',
                'institucion_emisora' => 'Institución de prueba',
                'fecha_emision' => '2025-05-15',
                'documento' => $archivo,
            ]);

            $respuesta->assertCreated()
                ->assertJsonPath('success', true);
        }

        $this->assertDatabaseCount('certificados', 2);

        $this->assertCount(
            2,
            Storage::disk('public')->allFiles('certificados')
        );
    }

    // 6. Rechazar formatos de archivo no permitidos.
    public function test_rechazar_formato_de_documento_invalido(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $respuesta = $this->enviarRegistro($token, [
            'nombre' => 'Certificado de prueba',
            'institucion_emisora' => 'Institución de prueba',
            'fecha_emision' => '2025-05-15',
            'documento' => UploadedFile::fake()->create(
                'certificado.txt',
                100,
                'text/plain'
            ),
        ]);

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors(['documento']);

        $this->assertDatabaseCount('certificados', 0);

        Storage::disk('public')
            ->assertDirectoryEmpty('certificados');
    }

    // 7. Rechazar documentos mayores a 5 MB.
    public function test_rechazar_documento_mayor_a_5_mb(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $respuesta = $this->enviarRegistro($token, [
            'nombre' => 'Certificado de prueba',
            'institucion_emisora' => 'Institución de prueba',
            'fecha_emision' => '2025-05-15',
            'documento' => UploadedFile::fake()->create(
                'certificado.pdf',
                5121,
                'application/pdf'
            ),
        ]);

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors(['documento']);

        $this->assertDatabaseCount('certificados', 0);
    }

    // 8. Validar los campos obligatorios.
    public function test_validar_campos_obligatorios(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $respuesta = $this->enviarRegistro($token, [
            'documento' => UploadedFile::fake()->create(
                'certificado.pdf',
                100,
                'application/pdf'
            ),
        ]);

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombre',
                'institucion_emisora',
                'fecha_emision',
            ]);

        $this->assertDatabaseCount('certificados', 0);
    }

    // 9. Rechazar fechas de emisión con formato incorrecto.
    public function test_rechazar_formato_de_fecha_emision_invalido(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $respuesta = $this->enviarRegistro($token, [
            'nombre' => 'Certificado de prueba',
            'institucion_emisora' => 'Institución de prueba',
            'fecha_emision' => '15-05-2025',
            'documento' => UploadedFile::fake()->create(
                'certificado.pdf',
                100,
                'application/pdf'
            ),
        ]);

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_emision']);

        $this->assertDatabaseCount('certificados', 0);
    }

    // 10. Rechazar vencimientos anteriores a la fecha de emisión.
    public function test_rechazar_fecha_vencimiento_anterior_a_emision(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $respuesta = $this->enviarRegistro($token, [
            'nombre' => 'Certificado de prueba',
            'institucion_emisora' => 'Institución de prueba',
            'fecha_emision' => '2025-05-15',
            'fecha_vencimiento' => '2025-04-15',
            'documento' => UploadedFile::fake()->create(
                'certificado.pdf',
                100,
                'application/pdf'
            ),
        ]);

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_vencimiento']);

        $this->assertDatabaseCount('certificados', 0);
    }

    // 11. Impedir que el cliente envíe o modifique verificado.
    public function test_impedir_modificar_estado_verificado(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $respuesta = $this->enviarRegistro($token, [
            'nombre' => 'Certificado de prueba',
            'institucion_emisora' => 'Institución de prueba',
            'fecha_emision' => '2025-05-15',
            'verificado' => true,
            'documento' => UploadedFile::fake()->create(
                'certificado.pdf',
                100,
                'application/pdf'
            ),
        ]);

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors(['verificado']);

        $this->assertDatabaseCount('certificados', 0);

        Storage::disk('public')
            ->assertDirectoryEmpty('certificados');
    }

    // 12. Eliminar correctamente un certificado propio y su documento.
    public function test_eliminar_certificado_correctamente(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $ruta = 'certificados/certificado-prueba.pdf';

        Storage::disk('public')->put(
            $ruta,
            'Contenido de prueba'
        );

        $certificado = $this->crearCertificado($perfil, [
            'ruta_documento' => $ruta,
        ]);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->deleteJson(
                '/api/v1/me/perfil/certificados/' .
                $certificado->id_certificado
            );

        $respuesta->assertNoContent();

        $this->assertDatabaseMissing('certificados', [
            'id_certificado' => $certificado->id_certificado,
        ]);

        Storage::disk('public')->assertMissing($ruta);
    }

    // 13. Impedir eliminar certificados de otros trabajadores.
    public function test_no_permitir_eliminar_certificado_de_otro_usuario(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $otroUsuario = $this->crearUsuario();
        $otroPerfil = $this->crearPerfil($otroUsuario);

        $ruta = 'certificados/certificado-ajeno.pdf';

        Storage::disk('public')->put(
            $ruta,
            'Contenido de prueba'
        );

        $certificadoAjeno = $this->crearCertificado(
            $otroPerfil,
            [
                'ruta_documento' => $ruta,
            ]
        );

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->deleteJson(
                '/api/v1/me/perfil/certificados/' .
                $certificadoAjeno->id_certificado
            );

        $respuesta->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'error_code',
                'CERTIFICATE_NOT_FOUND'
            );

        $this->assertDatabaseHas('certificados', [
            'id_certificado' => $certificadoAjeno->id_certificado,
        ]);

        Storage::disk('public')->assertExists($ruta);
    }

    // 14. Rechazar la eliminación de certificados inexistentes.
    public function test_eliminar_certificado_inexistente(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->deleteJson(
                '/api/v1/me/perfil/certificados/999999'
            );

        $respuesta->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'error_code',
                'CERTIFICATE_NOT_FOUND'
            );
    }
}
