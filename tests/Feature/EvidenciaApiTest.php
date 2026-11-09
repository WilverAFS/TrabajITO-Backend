<?php

namespace Tests\Feature;

use App\Models\Contratacion;
use App\Models\Estado;
use App\Models\EstadoContratacion;
use App\Models\Evidencia;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\Publicacion;
use App\Models\TipoPublicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenciaApiTest extends TestCase
{
    use DatabaseTruncation;

    private User $cliente;

    private User $usuarioTrabajador;

    private PerfilProfesional $trabajador;

    private Contratacion $contratacion;

    private Publicacion $publicacion;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->crearCatalogos();

        $this->cliente =
            $this->crearUsuario();

        $this->usuarioTrabajador =
            $this->crearUsuario();

        $this->trabajador =
            $this->crearPerfilProfesional(
                $this->usuarioTrabajador
            );

        $tipo =
            TipoPublicacion::create([
                'tipo_publicacion' =>
                'Reparación',
            ]);

        $this->contratacion =
            Contratacion::create([
                'id_cliente' =>
                $this->cliente->id_usuario,

                'id_trabajador' =>
                $this->trabajador->id_trabajador,

                'id_estado_contratacion' => 5,

                'descripcion_servicio' =>
                'Servicio finalizado.',

                'fecha_solicitud' =>
                now()->subDays(2),

                'fecha_respuesta' =>
                now()->subDay(),

                'fecha_finalizacion' =>
                now(),
            ]);

        $this->publicacion =
            Publicacion::create([
                'id_contratacion' =>
                $this->contratacion
                    ->id_contratacion,

                'id_tipo_publicacion' =>
                $tipo
                    ->id_tipo_publicacion,

                'titulo' =>
                'Trabajo realizado',

                'descripcion' =>
                'Descripción suficientemente extensa para cumplir validaciones.',

                'visible' => true,

                'fecha_publicacion' =>
                now(),
            ]);
    }

    private function crearCatalogos(): void
    {
        foreach (
            [
                'Pendiente',
                'Aceptada',
                'Rechazada',
                'En proceso',
                'Finalizada',
                'Cancelada',
            ] as $estado
        ) {

            EstadoContratacion::create([
                'estado_contratacion' =>
                $estado,
            ]);
        }
    }

    private function crearUsuario(): User
    {
        return User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' =>
            uniqid() . '@test.com',

            'contrasena_hash' =>
            'Password123!',
        ]);
    }

    private function crearAdministrador(): User
    {
        $admin =
            $this->crearUsuario();

        $admin->es_administrador =
            true;

        $admin->save();

        return $admin->fresh();
    }

    private function crearPerfilProfesional(
        User $usuario
    ): PerfilProfesional {

        $estado =
            Estado::create([
                'estado' =>
                'Estado ' . uniqid(),
            ]);

        $municipio =
            Municipio::create([
                'id_estado' =>
                $estado->id_estado,

                'municipio' =>
                'Municipio ' . uniqid(),
            ]);

        return PerfilProfesional::create([
            'id_usuario' =>
            $usuario->id_usuario,

            'id_municipio' =>
            $municipio->id_municipio,

            'descripcion' =>
            'Perfil de prueba',
        ]);
    }

    private function token(
        User $usuario
    ): string {

        return $usuario
            ->createToken(
                'Testing'
            )
            ->plainTextToken;
    }

    public function test_propietario_puede_subir_evidencias(): void
    {
        $archivo =
            UploadedFile::fake()
            ->create(
                'evidencia.jpg',
                500,
                'image/jpeg'
            );

        $response =
            $this
            ->withToken(
                $this->token(
                    $this->usuarioTrabajador
                )
            )
            ->postJson(
                "/api/v1/publicaciones/{$this->publicacion->id_publicacion}/evidencias",
                [
                    'archivos' => [
                        $archivo,
                    ],
                ]
            );

        $response->assertStatus(201);

        $this->assertDatabaseCount(
            'evidencias',
            1
        );
    }

    public function test_falla_si_publicacion_no_existe(): void
    {
        $archivo =
            UploadedFile::fake()
            ->create(
                'evidencia.jpg',
                500,
                'image/jpeg'
            );

        $this
            ->withToken(
                $this->token(
                    $this->usuarioTrabajador
                )
            )
            ->postJson(
                '/api/v1/publicaciones/999999/evidencias',
                [
                    'archivos' => [
                        $archivo,
                    ],
                ]
            )
            ->assertStatus(404);
    }

    public function test_usuario_no_propietario_no_puede_subir(): void
    {
        $otro =
            $this->crearUsuario();

        $archivo =
            UploadedFile::fake()
            ->create(
                'evidencia.jpg',
                500,
                'image/jpeg'
            );

        $this
            ->withToken(
                $this->token($otro)
            )
            ->postJson(
                "/api/v1/publicaciones/{$this->publicacion->id_publicacion}/evidencias",
                [
                    'archivos' => [
                        $archivo,
                    ],
                ]
            )
            ->assertStatus(403);
    }

    public function test_propietario_puede_eliminar_evidencia(): void
    {
        $evidencia =
            Evidencia::create([
                'id_publicacion' =>
                $this->publicacion
                    ->id_publicacion,

                'tipo_archivo' =>
                'IMAGEN',

                'ruta_archivo' =>
                'publicaciones/test.jpg',

                'fecha_carga' =>
                now(),
            ]);

        $this
            ->withToken(
                $this->token(
                    $this->usuarioTrabajador
                )
            )
            ->deleteJson(
                "/api/v1/evidencias/{$evidencia->id_evidencia}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'evidencias',
            [
                'id_evidencia' =>
                $evidencia
                    ->id_evidencia,
            ]
        );
    }

    public function test_administrador_puede_eliminar_evidencia(): void
    {
        $admin =
            $this->crearAdministrador();

        $evidencia =
            Evidencia::create([
                'id_publicacion' =>
                $this->publicacion
                    ->id_publicacion,

                'tipo_archivo' =>
                'IMAGEN',

                'ruta_archivo' =>
                'publicaciones/test.jpg',

                'fecha_carga' =>
                now(),
            ]);

        $this
            ->withToken(
                $this->token($admin)
            )
            ->deleteJson(
                "/api/v1/evidencias/{$evidencia->id_evidencia}"
            )
            ->assertNoContent();
    }

    public function test_usuario_sin_permisos_no_puede_eliminar(): void
    {
        $otro =
            $this->crearUsuario();

        $evidencia =
            Evidencia::create([
                'id_publicacion' =>
                $this->publicacion
                    ->id_publicacion,

                'tipo_archivo' =>
                'IMAGEN',

                'ruta_archivo' =>
                'publicaciones/test.jpg',

                'fecha_carga' =>
                now(),
            ]);

        $this
            ->withToken(
                $this->token($otro)
            )
            ->deleteJson(
                "/api/v1/evidencias/{$evidencia->id_evidencia}"
            )
            ->assertStatus(403);
    }

    public function test_no_permite_mas_de_diez_imagenes(): void
    {
        for ($i = 1; $i <= 10; $i++) {

            Evidencia::create([
                'id_publicacion' =>
                $this->publicacion
                    ->id_publicacion,

                'tipo_archivo' =>
                'IMAGEN',

                'ruta_archivo' =>
                "publicaciones/imagen-{$i}.jpg",

                'fecha_carga' =>
                now(),
            ]);
        }

        $archivo =
            UploadedFile::fake()
            ->create(
                'extra.jpg',
                500,
                'image/jpeg'
            );

        $this
            ->withToken(
                $this->token(
                    $this->usuarioTrabajador
                )
            )
            ->post(
                "/api/v1/publicaciones/{$this->publicacion->id_publicacion}/evidencias",
                [
                    'archivos' => [
                        $archivo,
                    ],
                ]
            )
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error_code' =>
                'IMAGE_LIMIT_EXCEEDED',
            ]);
    }

    public function test_no_permite_mas_de_tres_videos(): void
    {
        for ($i = 1; $i <= 3; $i++) {

            Evidencia::create([
                'id_publicacion' =>
                $this->publicacion
                    ->id_publicacion,

                'tipo_archivo' =>
                'VIDEO',

                'ruta_archivo' =>
                "publicaciones/video-{$i}.mp4",

                'fecha_carga' =>
                now(),
            ]);
        }

        $archivo =
            UploadedFile::fake()
            ->create(
                'extra.mp4',
                2048,
                'video/mp4'
            );

        $this
            ->withToken(
                $this->token(
                    $this->usuarioTrabajador
                )
            )
            ->post(
                "/api/v1/publicaciones/{$this->publicacion->id_publicacion}/evidencias",
                [
                    'archivos' => [
                        $archivo,
                    ],
                ]
            )
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error_code' =>
                'VIDEO_LIMIT_EXCEEDED',
            ]);
    }
}
