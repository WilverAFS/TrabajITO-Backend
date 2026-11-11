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
use App\Models\Valoracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValoracionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $cliente;
    private User $usuarioTrabajador;
    private PerfilProfesional $trabajador;
    private Contratacion $contratacion;
    private TipoPublicacion $tipoPublicacion;
    private Publicacion $publicacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearCatalogos();

        $this->cliente = $this->crearUsuario();
        $this->usuarioTrabajador = $this->crearUsuario();

        $this->trabajador = $this->crearPerfilProfesional(
            $this->usuarioTrabajador
        );

        $this->tipoPublicacion = TipoPublicacion::create([
            'tipo_publicacion' => 'Proyecto',
        ]);

        $this->contratacion = Contratacion::create([
            'id_cliente' => $this->cliente->id_usuario,
            'id_trabajador' => $this->trabajador->id_trabajador,
            'id_estado_contratacion' => 5,
            'descripcion_servicio' => 'Servicio concluido correctamente.',
            'fecha_solicitud' => now()->subDays(2),
            'fecha_respuesta' => now()->subDay(),
            'fecha_finalizacion' => now(),
        ]);

        $this->publicacion = Publicacion::create([
            'id_contratacion' => $this->contratacion->id_contratacion,
            'id_tipo_publicacion' => $this->tipoPublicacion->id_tipo_publicacion,
            'titulo' => 'Trabajo realizado correctamente',
            'descripcion' => 'Publicación del servicio finalizado para pruebas.',
            'visible' => true,
            'fecha_publicacion' => now(),
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
            'correo' => uniqid() . '@test.com',
            'contrasena_hash' => 'Password123!',
        ]);
    }

    private function crearPerfilProfesional(
        User $usuario
    ): PerfilProfesional {
        $estado = Estado::create([
            'estado' => 'Estado ' . uniqid(),
        ]);

        $municipio = Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Municipio ' . uniqid(),
        ]);

        return PerfilProfesional::create([
            'id_usuario' => $usuario->id_usuario,
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil profesional de prueba.',
        ]);
    }

    private function token(User $usuario): string
    {
        return $usuario
            ->createToken('Testing')
            ->plainTextToken;
    }

    private function urlCrear(): string
    {
        return "/api/v1/publicaciones/{$this->publicacion->id_publicacion}/valoracion";
    }

    private function urlActualizar(Valoracion $valoracion): string
    {
        return "/api/v1/valoraciones/{$valoracion->id_valoracion}";
    }

    private function crearValoracion(): Valoracion
    {
        return Valoracion::create([
            'id_publicacion' => $this->publicacion->id_publicacion,
            'id_cliente' => $this->cliente->id_usuario,
            'calificacion' => 5,
            'comentario' => 'Excelente servicio.',
            'fecha_valoracion' => now(),
        ]);
    }

    public function test_crear_valoracion_requiere_autenticacion(): void
    {
        $this->postJson($this->urlCrear(), [
            'calificacion' => 5,
        ])->assertUnauthorized();
    }

    public function test_cliente_puede_crear_valoracion(): void
    {
        $this
            ->withToken($this->token($this->cliente))
            ->postJson($this->urlCrear(), [
                'calificacion' => 5,
                'comentario' => 'Excelente trabajo realizado.',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.calificacion', 5)
            ->assertJsonPath(
                'data.cliente.id_usuario',
                $this->cliente->id_usuario
            )
            ->assertJsonPath(
                'data.cliente.nombre_completo',
                'Usuario Prueba'
            );

        $this->assertDatabaseHas('valoraciones', [
            'id_publicacion' => $this->publicacion->id_publicacion,
            'id_cliente' => $this->cliente->id_usuario,
            'calificacion' => 5,
        ]);
    }

    public function test_comentario_es_opcional(): void
    {
        $this
            ->withToken($this->token($this->cliente))
            ->postJson($this->urlCrear(), [
                'calificacion' => 4,
            ])
            ->assertCreated()
            ->assertJsonPath('data.comentario', null);
    }

    public function test_no_permite_calificacion_menor_a_uno(): void
    {
        $this
            ->withToken($this->token($this->cliente))
            ->postJson($this->urlCrear(), [
                'calificacion' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['calificacion']);

        $this->assertDatabaseCount('valoraciones', 0);
    }

    public function test_no_permite_calificacion_mayor_a_cinco(): void
    {
        $this
            ->withToken($this->token($this->cliente))
            ->postJson($this->urlCrear(), [
                'calificacion' => 6,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['calificacion']);
    }

    public function test_calificacion_es_obligatoria_al_crear(): void
    {
        $this
            ->withToken($this->token($this->cliente))
            ->postJson($this->urlCrear(), [
                'comentario' => 'Buen trabajo.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['calificacion']);
    }

    public function test_no_permite_valorar_publicacion_inexistente(): void
    {
        $this
            ->withToken($this->token($this->cliente))
            ->postJson(
                '/api/v1/publicaciones/999999/valoracion',
                ['calificacion' => 5]
            )
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'RESOURCE_NOT_FOUND'
            );
    }

    public function test_usuario_ajeno_no_puede_valorar(): void
    {
        $usuarioAjeno = $this->crearUsuario();

        $this
            ->withToken($this->token($usuarioAjeno))
            ->postJson($this->urlCrear(), [
                'calificacion' => 5,
            ])
            ->assertForbidden()
            ->assertJsonPath(
                'error_code',
                'NOT_HIRING_CLIENT'
            );

        $this->assertDatabaseCount('valoraciones', 0);
    }

    public function test_trabajador_no_puede_valorar_su_trabajo(): void
    {
        $this
            ->withToken($this->token($this->usuarioTrabajador))
            ->postJson($this->urlCrear(), [
                'calificacion' => 5,
            ])
            ->assertForbidden();
    }

    public function test_no_permite_valorar_contratacion_no_finalizada(): void
    {
        $this->contratacion->update([
            'id_estado_contratacion' => 4,
            'fecha_finalizacion' => null,
        ]);

        $this
            ->withToken($this->token($this->cliente))
            ->postJson($this->urlCrear(), [
                'calificacion' => 5,
            ])
            ->assertStatus(409)
            ->assertJsonPath(
                'error_code',
                'HIRING_NOT_COMPLETED'
            );

        $this->assertDatabaseCount('valoraciones', 0);
    }

    public function test_no_permite_valoraciones_duplicadas(): void
    {
        $this->crearValoracion();

        $this
            ->withToken($this->token($this->cliente))
            ->postJson($this->urlCrear(), [
                'calificacion' => 4,
            ])
            ->assertStatus(409)
            ->assertJsonPath(
                'error_code',
                'RATING_ALREADY_EXISTS'
            );

        $this->assertDatabaseCount('valoraciones', 1);
    }

    public function test_actualizar_valoracion_requiere_autenticacion(): void
    {
        $valoracion = $this->crearValoracion();

        $this
            ->patchJson($this->urlActualizar($valoracion), [
                'calificacion' => 4,
            ])
            ->assertUnauthorized();
    }

    public function test_autor_puede_actualizar_valoracion(): void
    {
        $valoracion = $this->crearValoracion();

        $this
            ->withToken($this->token($this->cliente))
            ->patchJson($this->urlActualizar($valoracion), [
                'calificacion' => 4,
                'comentario' => 'Buen trabajo, con algunos detalles.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.calificacion', 4)
            ->assertJsonPath(
                'data.comentario',
                'Buen trabajo, con algunos detalles.'
            );

        $this->assertDatabaseHas('valoraciones', [
            'id_valoracion' => $valoracion->id_valoracion,
            'calificacion' => 4,
            'comentario' => 'Buen trabajo, con algunos detalles.',
        ]);
    }

    public function test_permite_actualizacion_parcial(): void
    {
        $valoracion = $this->crearValoracion();

        $this
            ->withToken($this->token($this->cliente))
            ->patchJson($this->urlActualizar($valoracion), [
                'comentario' => 'Comentario actualizado.',
            ])
            ->assertOk()
            ->assertJsonPath('data.calificacion', 5)
            ->assertJsonPath(
                'data.comentario',
                'Comentario actualizado.'
            );
    }

    public function test_usuario_ajeno_no_puede_actualizar(): void
    {
        $valoracion = $this->crearValoracion();
        $usuarioAjeno = $this->crearUsuario();

        $this
            ->withToken($this->token($usuarioAjeno))
            ->patchJson($this->urlActualizar($valoracion), [
                'calificacion' => 1,
            ])
            ->assertForbidden()
            ->assertJsonPath(
                'error_code',
                'FORBIDDEN'
            );

        $this->assertDatabaseHas('valoraciones', [
            'id_valoracion' => $valoracion->id_valoracion,
            'calificacion' => 5,
        ]);
    }

    public function test_no_actualiza_valoracion_de_publicacion_oculta(): void
    {
        $valoracion = $this->crearValoracion();

        $this->publicacion->update([
            'visible' => false,
        ]);

        $this
            ->withToken($this->token($this->cliente))
            ->patchJson($this->urlActualizar($valoracion), [
                'calificacion' => 3,
            ])
            ->assertStatus(409)
            ->assertJsonPath(
                'error_code',
                'OPERATION_FAILED'
            );

        $this->assertDatabaseHas('valoraciones', [
            'id_valoracion' => $valoracion->id_valoracion,
            'calificacion' => 5,
        ]);
    }

    public function test_no_actualiza_valoracion_inexistente(): void
    {
        $this
            ->withToken($this->token($this->cliente))
            ->patchJson(
                '/api/v1/valoraciones/999999',
                ['calificacion' => 4]
            )
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'RESOURCE_NOT_FOUND'
            );
    }

    public function test_no_permite_calificacion_invalida_al_actualizar(): void
    {
        $valoracion = $this->crearValoracion();

        $this
            ->withToken($this->token($this->cliente))
            ->patchJson($this->urlActualizar($valoracion), [
                'calificacion' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['calificacion']);
    }

    public function test_actualizacion_requiere_al_menos_un_campo(): void
    {
        $valoracion = $this->crearValoracion();

        $this
            ->withToken($this->token($this->cliente))
            ->patchJson(
                $this->urlActualizar($valoracion),
                []
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error_code',
                'VALIDATION_ERROR'
            );
    }
}
