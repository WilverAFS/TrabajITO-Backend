<?php

namespace Tests\Feature;

use App\Models\Contratacion;
use App\Models\Estado;
use App\Models\EstadoContratacion;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\Publicacion;
use App\Models\TipoNotificacion;
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
        TipoNotificacion::firstOrCreate(
            [
                'tipo_notificacion' => 'PUBLICACION',
            ],
            [
                'id_tipo_notificacion' => 3,
            ]
        );

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
                'tipo_publicacion' => 'Proyecto',
            ]);

        $this->contratacion =
            Contratacion::create([
                'id_cliente' => $this->cliente->id_usuario,

                'id_trabajador' => $this->trabajador->id_trabajador,

                'id_estado_contratacion' => 5,

                'descripcion_servicio' => 'Servicio concluido correctamente.',

                'fecha_solicitud' => now()->subDays(2),

                'fecha_respuesta' => now()->subDay(),

                'fecha_finalizacion' => now(),
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
                'estado_contratacion' => $estado,
            ]);
        }
    }

    private function crearUsuario(): User
    {
        return User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => uniqid().'@test.com',
            'contrasena_hash' => 'Password123!',
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
            'estado' => 'Estado '.uniqid(),
        ]);

        $municipio = Municipio::create([
            'id_estado' => $estado->id_estado,

            'municipio' => 'Municipio '.uniqid(),
        ]);

        return PerfilProfesional::create([
            'id_usuario' => $usuario->id_usuario,

            'id_municipio' => $municipio->id_municipio,

            'descripcion' => 'Perfil profesional de prueba.',
        ]);
    }

    private function token(
        User $usuario
    ): string {

        return $usuario
            ->createToken('Testing')
            ->plainTextToken;
    }

    private function crearPublicacion(
        string $titulo,
        string $descripcion,
        bool $visible = true
    ): Publicacion {
        $contratacion = Contratacion::create([
            'id_cliente' => $this->cliente->id_usuario,
            'id_trabajador' => $this->trabajador->id_trabajador,
            'id_estado_contratacion' => 5,
            'descripcion_servicio' => 'Contratación para prueba administrativa.',
            'fecha_solicitud' => now()->subDays(2),
            'fecha_respuesta' => now()->subDay(),
            'fecha_finalizacion' => now(),
        ]);

        return Publicacion::create([
            'id_contratacion' => $contratacion->id_contratacion,
            'id_tipo_publicacion' => $this->tipoPublicacion->id_tipo_publicacion,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'visible' => $visible,
            'fecha_publicacion' => now(),
        ]);
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
                    'id_tipo_publicacion' => $this->tipoPublicacion
                        ->id_tipo_publicacion,

                    'titulo' => 'Instalación eléctrica residencial',

                    'descripcion' => 'Trabajo realizado satisfactoriamente para el cliente utilizando materiales certificados.',
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
                'id_contratacion' => $this->contratacion
                    ->id_contratacion,
            ]
        );
    }

    public function test_no_permite_publicaciones_duplicadas(): void
    {
        Publicacion::create([
            'id_contratacion' => $this->contratacion
                ->id_contratacion,

            'id_tipo_publicacion' => $this->tipoPublicacion
                ->id_tipo_publicacion,

            'titulo' => 'Publicación inicial válida',

            'descripcion' => 'Esta publicación ya existe para la contratación de prueba.',

            'visible' => true,

            'fecha_publicacion' => now(),
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
                    'id_tipo_publicacion' => $this->tipoPublicacion
                        ->id_tipo_publicacion,

                    'titulo' => 'Intento de duplicar publicación',

                    'descripcion' => 'Se intenta crear una segunda publicación para la misma contratación.',
                ]
            )
            ->assertStatus(409);
    }

    public function test_puede_consultar_publicacion(): void
    {
        $publicacion =
            Publicacion::create([
                'id_contratacion' => $this->contratacion
                    ->id_contratacion,

                'id_tipo_publicacion' => $this->tipoPublicacion
                    ->id_tipo_publicacion,

                'titulo' => 'Publicación visible válida',

                'descripcion' => 'Descripción suficientemente extensa para cumplir con la validación.',

                'visible' => true,

                'fecha_publicacion' => now(),
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
                'id_contratacion' => $this->contratacion
                    ->id_contratacion,

                'id_tipo_publicacion' => $this->tipoPublicacion
                    ->id_tipo_publicacion,

                'titulo' => 'Titulo inicial válido',

                'descripcion' => 'Descripción inicial suficientemente extensa para superar la validación.',

                'visible' => true,

                'fecha_publicacion' => now(),
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
                    'titulo' => 'Titulo actualizado correctamente',
                ]
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'publicaciones',
            [
                'id_publicacion' => $publicacion
                    ->id_publicacion,

                'titulo' => 'Titulo actualizado correctamente',
            ]
        );
    }

    public function test_puede_consultar_mis_publicaciones(): void
    {
        Publicacion::create([
            'id_contratacion' => $this->contratacion
                ->id_contratacion,

            'id_tipo_publicacion' => $this->tipoPublicacion
                ->id_tipo_publicacion,

            'titulo' => 'Mi publicación profesional',

            'descripcion' => 'Publicación creada exclusivamente para validar este endpoint.',

            'visible' => true,

            'fecha_publicacion' => now(),
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
            'id_contratacion' => $this->contratacion
                ->id_contratacion,

            'id_tipo_publicacion' => $this->tipoPublicacion
                ->id_tipo_publicacion,

            'titulo' => 'Portafolio de trabajo realizado',

            'descripcion' => 'Publicación visible para consulta pública del trabajador.',

            'visible' => true,

            'fecha_publicacion' => now(),
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
                'id_contratacion' => $this->contratacion
                    ->id_contratacion,

                'id_tipo_publicacion' => $this->tipoPublicacion
                    ->id_tipo_publicacion,

                'titulo' => 'Publicación a ocultar',

                'descripcion' => 'Descripción válida para probar ocultamiento administrativo.',

                'visible' => true,

                'fecha_publicacion' => now(),
            ]);

        $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                "/api/v1/admin/publicaciones/{$publicacion->id_publicacion}/ocultar",
                [
                    'motivo' => 'La publicación contiene información que requiere corrección.',
                ]
            )
            ->assertOk();

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $this->trabajador->id_usuario,
            'titulo' => 'Tu publicación ha sido ocultada',
            'mensaje' => 'Tu publicación "'.$publicacion->titulo
                .'" fue ocultada por un administrador. Motivo: '
                .'La publicación contiene información que requiere corrección.',
        ]);

    }

    public function test_ocultar_publicacion_requiere_motivo(): void
    {
        $admin = $this->crearAdministrador();

        $publicacion = Publicacion::create([
            'id_contratacion' => $this->contratacion->id_contratacion,
            'id_tipo_publicacion' => $this->tipoPublicacion->id_tipo_publicacion,
            'titulo' => 'Publicación de prueba',
            'descripcion' => 'Descripción válida para la prueba.',
            'visible' => true,
            'fecha_publicacion' => now(),
        ]);

        $this->withToken($this->token($admin))
            ->patchJson(
                "/api/v1/admin/publicaciones/{$publicacion->id_publicacion}/ocultar",
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motivo');

        $this->assertDatabaseHas('publicaciones', [
            'id_publicacion' => $publicacion->id_publicacion,
            'visible' => true,
        ]);
    }

    public function test_no_notifica_dos_veces_al_ocultar_publicacion(): void
    {
        $admin = $this->crearAdministrador();

        $publicacion = Publicacion::create([
            'id_contratacion' => $this->contratacion->id_contratacion,
            'id_tipo_publicacion' => $this->tipoPublicacion->id_tipo_publicacion,
            'titulo' => 'Publicación de prueba',
            'descripcion' => 'Descripción válida para la prueba.',
            'visible' => true,
            'fecha_publicacion' => now(),
        ]);

        $url = "/api/v1/admin/publicaciones/{$publicacion->id_publicacion}/ocultar";

        $this->withToken($this->token($admin))
            ->patchJson($url, [
                'motivo' => 'Requiere correcciones.',
            ])
            ->assertOk();

        $this->patchJson($url, [
            'motivo' => 'Segundo intento de ocultamiento.',
        ])
            ->assertStatus(409)
            ->assertJsonPath(
                'error_code',
                'PUBLICATION_ALREADY_HIDDEN'
            );

        $this->assertDatabaseCount('notificaciones', 1);
    }

    public function test_administrador_puede_consultar_listado_de_publicaciones(): void
    {
        $admin = $this->crearAdministrador();

        $this->crearPublicacion(
            'Proyecto administrativo',
            'Descripción de prueba administrativa.',
            true
        );

        $this->crearPublicacion(
            'Proyecto oculto',
            'Descripción de publicación oculta.',
            false
        );

        $this->withToken($this->token($admin))
            ->getJson('/api/v1/admin/publicaciones')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.total_ocultas', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_administrador_puede_filtrar_publicaciones_visibles(): void
    {
        $admin = $this->crearAdministrador();

        $this->crearPublicacion(
            'Proyecto visible',
            'Descripción visible.',
            true
        );

        $this->crearPublicacion(
            'Proyecto oculto',
            'Descripción oculta.',
            false
        );

        $this->withToken($this->token($admin))
            ->getJson('/api/v1/admin/publicaciones?visible=true')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.visible', true);
    }

    public function test_administrador_puede_filtrar_publicaciones_ocultas(): void
    {
        $admin = $this->crearAdministrador();

        $this->crearPublicacion(
            'Proyecto visible',
            'Descripción visible.',
            true
        );

        $this->crearPublicacion(
            'Proyecto oculto',
            'Descripción oculta.',
            false
        );

        $this->withToken($this->token($admin))
            ->getJson('/api/v1/admin/publicaciones?visible=false')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.visible', false);
    }

    public function test_administrador_puede_buscar_por_titulo_y_descripcion(): void
    {
        $admin = $this->crearAdministrador();

        $this->crearPublicacion(
            'Instalación residencial',
            'Trabajo eléctrico realizado.',
            true
        );

        $this->crearPublicacion(
            'Reparación general',
            'Instalación de paneles solares.',
            true
        );

        $this->crearPublicacion(
            'Pintura de interiores',
            'Trabajo de pintura.',
            true
        );

        $this->withToken($this->token($admin))
            ->getJson('/api/v1/admin/publicaciones?search=Instalación')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->withToken($this->token($admin))
            ->getJson('/api/v1/admin/publicaciones?buscar=Instalación')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_listado_administrativo_pagina_correctamente(): void
    {
        $admin = $this->crearAdministrador();

        for ($i = 1; $i <= 17; $i++) {
            $this->crearPublicacion(
                "Proyecto de prueba {$i}",
                "Descripción del proyecto {$i}.",
                true
            );
        }

        $this->withToken($this->token($admin))
            ->getJson('/api/v1/admin/publicaciones')
            ->assertOk()
            ->assertJsonPath('meta.total', 17)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(15, 'data');

        $this->withToken($this->token($admin))
            ->getJson('/api/v1/admin/publicaciones?page=2')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_administrador_puede_mostrar_publicacion_oculta(): void
    {
        $admin = $this->crearAdministrador();

        $publicacion = $this->crearPublicacion(
            'Publicación oculta de prueba',
            'Descripción para probar la restauración.',
            false
        );

        $this->withToken($this->token($admin))
            ->patchJson(
                "/api/v1/admin/publicaciones/{$publicacion->id_publicacion}/mostrar"
            )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.visible', true);

        $this->assertDatabaseHas('publicaciones', [
            'id_publicacion' => $publicacion->id_publicacion,
            'visible' => true,
        ]);
    }

    public function test_no_permite_mostrar_una_publicacion_ya_visible(): void
    {
        $admin = $this->crearAdministrador();

        $publicacion = $this->crearPublicacion(
            'Publicación visible de prueba',
            'Descripción para probar el conflicto.',
            true
        );

        $this->withToken($this->token($admin))
            ->patchJson(
                "/api/v1/admin/publicaciones/{$publicacion->id_publicacion}/mostrar"
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'error_code',
                'PUBLICATION_ALREADY_VISIBLE'
            );
    }

    public function test_mostrar_publicacion_inexistente_devuelve_404(): void
    {
        $admin = $this->crearAdministrador();

        $this->withToken($this->token($admin))
            ->patchJson('/api/v1/admin/publicaciones/999999/mostrar')
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'PUBLICATION_NOT_FOUND'
            );
    }

    public function test_trabajador_no_puede_mostrar_publicaciones(): void
    {
        $publicacion = $this->crearPublicacion(
            'Publicación oculta de prueba',
            'Descripción para probar los permisos.',
            false
        );

        $this->withToken($this->token($this->usuarioTrabajador))
            ->patchJson(
                "/api/v1/admin/publicaciones/{$publicacion->id_publicacion}/mostrar"
            )
            ->assertForbidden();

        $this->assertDatabaseHas('publicaciones', [
            'id_publicacion' => $publicacion->id_publicacion,
            'visible' => false,
        ]);
    }
}
