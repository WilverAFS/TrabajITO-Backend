<?php

namespace Tests\Feature;

use App\Models\Contratacion;
use App\Models\Estado;
use App\Models\EstadoContratacion;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\Publicacion;
use App\Models\TipoPublicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicacionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $cliente;

    private User $usuarioTrabajador;

    private PerfilProfesional $trabajador;

    private Contratacion $contratacion;

    private TipoPublicacion $tipoPublicacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearCatalogos();

        $this->cliente =
            $this->crearUsuario();

        $this->usuarioTrabajador =
            $this->crearUsuario();

        $this->trabajador =
            $this->crearPerfilProfesional(
                $this->usuarioTrabajador
            );

        $this->tipoPublicacion =
            TipoPublicacion::create([
                'tipo_publicacion' =>
                    'Proyecto',
            ]);

        $this->contratacion =
            Contratacion::create([
                'id_cliente' =>
                    $this->cliente->id_usuario,

                'id_trabajador' =>
                    $this->trabajador->id_trabajador,

                'id_estado_contratacion' => 5,

                'descripcion_servicio' =>
                    'Servicio concluido correctamente.',

                'fecha_solicitud' =>
                    now()->subDays(2),

                'fecha_respuesta' =>
                    now()->subDay(),

                'fecha_finalizacion' =>
                    now(),
            ]);
    }

    private function crearCatalogos(): void
    {
        foreach ([
            'Pendiente',
            'Aceptada',
            'Rechazada',
            'En proceso',
            'Finalizada',
            'Cancelada',
        ] as $estado) {

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
        $admin = $this->crearUsuario();

        $admin->es_administrador = true;
        $admin->save();

        return $admin->fresh();
    }

    private function crearPerfilProfesional(
        User $usuario
    ): PerfilProfesional {

        $estado = Estado::create([
            'estado' =>
                'Estado ' . uniqid(),
        ]);

        $municipio = Municipio::create([
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
                'Perfil profesional de prueba.',
        ]);
    }

    private function token(
        User $usuario
    ): string {

        return $usuario
            ->createToken('Testing')
            ->plainTextToken;
    }

    public function test_trabajador_puede_crear_publicacion(): void
    {
        $response = $this
            ->withToken(
                $this->token(
                    $this->usuarioTrabajador
                )
            )
            ->postJson(
                "/api/v1/contrataciones/{$this->contratacion->id_contratacion}/publicacion",
                [
                    'id_tipo_publicacion' =>
                        $this->tipoPublicacion
                            ->id_tipo_publicacion,

                    'titulo' =>
                        'Instalación eléctrica residencial',

                    'descripcion' =>
                        'Trabajo realizado satisfactoriamente para el cliente utilizando materiales certificados.',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertDatabaseHas(
            'publicaciones',
            [
                'id_contratacion' =>
                    $this->contratacion
                        ->id_contratacion,
            ]
        );
    }

    public function test_no_permite_publicaciones_duplicadas(): void
    {
        Publicacion::create([
            'id_contratacion' =>
                $this->contratacion
                    ->id_contratacion,

            'id_tipo_publicacion' =>
                $this->tipoPublicacion
                    ->id_tipo_publicacion,

            'titulo' =>
                'Publicación inicial válida',

            'descripcion' =>
                'Esta publicación ya existe para la contratación de prueba.',

            'visible' => true,

            'fecha_publicacion' =>
                now(),
        ]);

        $this
            ->withToken(
                $this->token(
                    $this->usuarioTrabajador
                )
            )
            ->postJson(
                "/api/v1/contrataciones/{$this->contratacion->id_contratacion}/publicacion",
                [
                    'id_tipo_publicacion' =>
                        $this->tipoPublicacion
                            ->id_tipo_publicacion,

                    'titulo' =>
                        'Intento de duplicar publicación',

                    'descripcion' =>
                        'Se intenta crear una segunda publicación para la misma contratación.',
                ]
            )
            ->assertStatus(409);
    }

    public function test_puede_consultar_publicacion(): void
    {
        $publicacion =
            Publicacion::create([
                'id_contratacion' =>
                    $this->contratacion
                        ->id_contratacion,

                'id_tipo_publicacion' =>
                    $this->tipoPublicacion
                        ->id_tipo_publicacion,

                'titulo' =>
                    'Publicación visible válida',

                'descripcion' =>
                    'Descripción suficientemente extensa para cumplir con la validación.',

                'visible' => true,

                'fecha_publicacion' =>
                    now(),
            ]);

        $this
            ->getJson(
                "/api/v1/publicaciones/{$publicacion->id_publicacion}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.id_publicacion',
                $publicacion->id_publicacion
            );
    }

    public function test_propietario_puede_actualizar_publicacion(): void
    {
        $publicacion =
            Publicacion::create([
                'id_contratacion' =>
                    $this->contratacion
                        ->id_contratacion,

                'id_tipo_publicacion' =>
                    $this->tipoPublicacion
                        ->id_tipo_publicacion,

                'titulo' =>
                    'Titulo inicial válido',

                'descripcion' =>
                    'Descripción inicial suficientemente extensa para superar la validación.',

                'visible' => true,

                'fecha_publicacion' =>
                    now(),
            ]);

        $this
            ->withToken(
                $this->token(
                    $this->usuarioTrabajador
                )
            )
            ->patchJson(
                "/api/v1/publicaciones/{$publicacion->id_publicacion}",
                [
                    'titulo' =>
                        'Titulo actualizado correctamente',
                ]
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'publicaciones',
            [
                'id_publicacion' =>
                    $publicacion
                        ->id_publicacion,

                'titulo' =>
                    'Titulo actualizado correctamente',
            ]
        );
    }

    public function test_puede_consultar_mis_publicaciones(): void
    {
        Publicacion::create([
            'id_contratacion' =>
                $this->contratacion
                    ->id_contratacion,

            'id_tipo_publicacion' =>
                $this->tipoPublicacion
                    ->id_tipo_publicacion,

            'titulo' =>
                'Mi publicación profesional',

            'descripcion' =>
                'Publicación creada exclusivamente para validar este endpoint.',

            'visible' => true,

            'fecha_publicacion' =>
                now(),
        ]);

        $this
            ->withToken(
                $this->token(
                    $this->usuarioTrabajador
                )
            )
            ->getJson(
                '/api/v1/me/perfil/publicaciones'
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            );
    }

    public function test_puede_consultar_publicaciones_por_trabajador(): void
    {
        Publicacion::create([
            'id_contratacion' =>
                $this->contratacion
                    ->id_contratacion,

            'id_tipo_publicacion' =>
                $this->tipoPublicacion
                    ->id_tipo_publicacion,

            'titulo' =>
                'Portafolio de trabajo realizado',

            'descripcion' =>
                'Publicación visible para consulta pública del trabajador.',

            'visible' => true,

            'fecha_publicacion' =>
                now(),
        ]);

        $this
            ->getJson(
                "/api/v1/publicaciones/trabajador/{$this->trabajador->id_trabajador}"
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            );
    }

    public function test_administrador_puede_ocultar_publicacion(): void
    {
        $admin =
            $this->crearAdministrador();

        $this->assertTrue(
            $admin->es_administrador
        );

        $publicacion =
            Publicacion::create([
                'id_contratacion' =>
                    $this->contratacion
                        ->id_contratacion,

                'id_tipo_publicacion' =>
                    $this->tipoPublicacion
                        ->id_tipo_publicacion,

                'titulo' =>
                    'Publicación a ocultar',

                'descripcion' =>
                    'Descripción válida para probar ocultamiento administrativo.',

                'visible' => true,

                'fecha_publicacion' =>
                    now(),
            ]);

        $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                "/api/v1/admin/publicaciones/{$publicacion->id_publicacion}/ocultar"
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'publicaciones',
            [
                'id_publicacion' =>
                    $publicacion
                        ->id_publicacion,

                'visible' => false,
            ]
        );
    }
}