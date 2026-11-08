<?php

namespace Tests\Feature;

use App\Models\Contratacion;
use App\Models\Estado;
use App\Models\EstadoContratacion;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class ContratacionTransicionApiTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'Pendiente',
            'Aceptada',
            'Rechazada',
            'En proceso',
            'Finalizada',
            'Cancelada',
        ] as $nombre) {
            EstadoContratacion::firstOrCreate([
                'estado_contratacion' => $nombre,
            ]);
        }
    }

    private function crearUsuario(
        bool $activo = true
    ): User {
        $usuario = User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => uniqid() . '@test.com',
            'telefono' => null,
            'contrasena_hash' => 'Prueba123!',
        ]);

        $usuario->activo = $activo;
        $usuario->save();

        return $usuario;
    }

    private function crearPerfil(
        User $usuario
    ): PerfilProfesional {
        $sufijo = uniqid();

        $estado = Estado::create([
            'estado' => 'Estado ' . $sufijo,
            'activo' => true,
        ]);

        $municipio = Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Municipio ' . $sufijo,
            'activo' => true,
        ]);

        return $usuario
            ->perfilProfesional()
            ->create([
                'id_municipio' => $municipio->id_municipio,
                'descripcion' => 'Profesional de prueba',
            ]);
    }

    private function token(
        User $usuario
    ): string {
        return $usuario
            ->createToken('Pruebas Transiciones')
            ->plainTextToken;
    }

    private function crearContratacion(
        User $cliente,
        PerfilProfesional $trabajador,
        int $estado
    ): Contratacion {
        return Contratacion::create([
            'id_cliente' => $cliente->id_usuario,
            'id_trabajador' =>
                $trabajador->id_trabajador,
            'id_estado_contratacion' => $estado,
            'descripcion_servicio' =>
                'Servicio de prueba',
        ]);
    }

    public function test_requiere_autenticacion(): void
    {
        $this->postJson(
            '/api/v1/contrataciones/1/aceptar'
        )->assertUnauthorized();
    }

    public function test_trabajador_puede_aceptar_contratacion(): void
    {
        $cliente = $this->crearUsuario();

        $usuarioTrabajador =
            $this->crearUsuario();

        $perfil =
            $this->crearPerfil(
                $usuarioTrabajador
            );

        $contratacion =
            $this->crearContratacion(
                $cliente,
                $perfil,
                1
            );

        $this
            ->withToken(
                $this->token(
                    $usuarioTrabajador
                )
            )
            ->postJson(
                "/api/v1/contrataciones/{$contratacion->id_contratacion}/aceptar"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.estado.id_estado_contratacion',
                2
            );

        $this->assertDatabaseHas(
            'contrataciones',
            [
                'id_contratacion' =>
                    $contratacion
                        ->id_contratacion,
                'id_estado_contratacion' => 2,
            ]
        );
    }

    public function test_trabajador_puede_rechazar_contratacion(): void
    {
        $cliente = $this->crearUsuario();

        $usuarioTrabajador =
            $this->crearUsuario();

        $perfil =
            $this->crearPerfil(
                $usuarioTrabajador
            );

        $contratacion =
            $this->crearContratacion(
                $cliente,
                $perfil,
                1
            );

        $this
            ->withToken(
                $this->token(
                    $usuarioTrabajador
                )
            )
            ->postJson(
                "/api/v1/contrataciones/{$contratacion->id_contratacion}/rechazar"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.estado.id_estado_contratacion',
                3
            );
    }

    public function test_trabajador_puede_iniciar_contratacion(): void
    {
        $cliente = $this->crearUsuario();

        $usuarioTrabajador =
            $this->crearUsuario();

        $perfil =
            $this->crearPerfil(
                $usuarioTrabajador
            );

        $contratacion =
            $this->crearContratacion(
                $cliente,
                $perfil,
                2
            );

        $this
            ->withToken(
                $this->token(
                    $usuarioTrabajador
                )
            )
            ->postJson(
                "/api/v1/contrataciones/{$contratacion->id_contratacion}/iniciar"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.estado.id_estado_contratacion',
                4
            );
    }

    public function test_trabajador_puede_finalizar_contratacion(): void
    {
        $cliente = $this->crearUsuario();

        $usuarioTrabajador =
            $this->crearUsuario();

        $perfil =
            $this->crearPerfil(
                $usuarioTrabajador
            );

        $contratacion =
            $this->crearContratacion(
                $cliente,
                $perfil,
                4
            );

        $this
            ->withToken(
                $this->token(
                    $usuarioTrabajador
                )
            )
            ->postJson(
                "/api/v1/contrataciones/{$contratacion->id_contratacion}/finalizar"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.estado.id_estado_contratacion',
                5
            );

        $this->assertDatabaseMissing(
            'contrataciones',
            [
                'id_contratacion' =>
                    $contratacion
                        ->id_contratacion,
                'fecha_finalizacion' => null,
            ]
        );
    }

    public function test_cliente_puede_cancelar_contratacion(): void
    {
        $cliente = $this->crearUsuario();

        $usuarioTrabajador =
            $this->crearUsuario();

        $perfil =
            $this->crearPerfil(
                $usuarioTrabajador
            );

        $contratacion =
            $this->crearContratacion(
                $cliente,
                $perfil,
                1
            );

        $this
            ->withToken(
                $this->token($cliente)
            )
            ->postJson(
                "/api/v1/contrataciones/{$contratacion->id_contratacion}/cancelar"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.estado.id_estado_contratacion',
                6
            );
    }

    public function test_usuario_ajeno_no_puede_modificar_contratacion(): void
    {
        $cliente = $this->crearUsuario();

        $usuarioTrabajador =
            $this->crearUsuario();

        $usuarioAjeno =
            $this->crearUsuario();

        $perfil =
            $this->crearPerfil(
                $usuarioTrabajador
            );

        $contratacion =
            $this->crearContratacion(
                $cliente,
                $perfil,
                1
            );

        $this
            ->withToken(
                $this->token(
                    $usuarioAjeno
                )
            )
            ->postJson(
                "/api/v1/contrataciones/{$contratacion->id_contratacion}/aceptar"
            )
            ->assertForbidden()
            ->assertJsonPath(
                'error_code',
                'HIRING_ACCESS_DENIED'
            );
    }

    public function test_no_permite_transicion_invalida(): void
    {
        $cliente = $this->crearUsuario();

        $usuarioTrabajador =
            $this->crearUsuario();

        $perfil =
            $this->crearPerfil(
                $usuarioTrabajador
            );

        $contratacion =
            $this->crearContratacion(
                $cliente,
                $perfil,
                3
            );

        $this
            ->withToken(
                $this->token(
                    $usuarioTrabajador
                )
            )
            ->postJson(
                "/api/v1/contrataciones/{$contratacion->id_contratacion}/aceptar"
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'error_code',
                'INVALID_STATE_TRANSITION'
            );
    }

    public function test_cliente_no_puede_aceptar_contratacion(): void
    {
        $cliente = $this->crearUsuario();

        $usuarioTrabajador =
            $this->crearUsuario();

        $perfil =
            $this->crearPerfil(
                $usuarioTrabajador
            );

        $contratacion =
            $this->crearContratacion(
                $cliente,
                $perfil,
                1
            );

        $this
            ->withToken(
                $this->token($cliente)
            )
            ->postJson(
                "/api/v1/contrataciones/{$contratacion->id_contratacion}/aceptar"
            )
            ->assertForbidden();
    }
}