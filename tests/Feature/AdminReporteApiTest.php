<?php

namespace Tests\Feature;

use App\Models\EstadoReporte;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReporteApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = '/api/v1/admin/reportes';

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'Pendiente',
            'En revisión',
            'Concluido',
        ] as $estado) {
            EstadoReporte::create([
                'estado_reporte' => $estado,
            ]);
        }
    }

    private function crearUsuario(
        bool $administrador = false,
        bool $activo = true
    ): User {
        $usuario = User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => uniqid().'@test.com',
            'contrasena_hash' => 'Password123!',
        ]);

        $usuario->es_administrador = $administrador;
        $usuario->activo = $activo;
        $usuario->save();

        return $usuario->fresh();
    }

    private function token(
        User $usuario
    ): string {
        return $usuario
            ->createToken('Testing')
            ->plainTextToken;
    }

    private function crearReporte(
        User $denunciante,
        int $estado = 1,
        string $tipoObjetivo = 'PUBLICACION'
    ): Reporte {
        return Reporte::create([
            'id_denunciante' => $denunciante->id_usuario,
            'tipo_objetivo' => $tipoObjetivo,
            'id_objetivo' => 1,
            'motivo' => 'Contenido engañoso',
            'descripcion' => 'Descripción de prueba.',
            'id_estado_reporte' => $estado,
            'fecha_creacion' => now(),
        ]);
    }

    public function test_requiere_autenticacion(): void
    {
        $response = $this->getJson(
            self::BASE_URL
        );

        $response->assertUnauthorized();
    }

    public function test_requiere_administrador(): void
    {
        $usuario = $this->crearUsuario(
            administrador: false
        );

        $response = $this
            ->withToken(
                $this->token($usuario)
            )
            ->getJson(
                self::BASE_URL
            );

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'No tienes permisos para acceder a este recurso.',
                'error_code' => 'FORBIDDEN',
                'errors' => null,
            ]);
    }

    public function test_listar_reportes(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $denunciante = $this->crearUsuario();

        $this->crearReporte($denunciante, 1);
        $this->crearReporte($denunciante, 2);

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->getJson(
                self::BASE_URL
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Reportes obtenidos correctamente.',
            ]);

        $this->assertCount(
            2,
            $response->json('data')
        );
    }

    public function test_filtrar_por_estado(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $denunciante = $this->crearUsuario();

        $pendiente = $this->crearReporte(
            $denunciante,
            1
        );

        $this->crearReporte(
            $denunciante,
            2
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->getJson(
                self::BASE_URL.
                '?id_estado=1'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            );

        $this->assertEquals(
            $pendiente->id_reporte,
            $response->json('data.0.id_reporte')
        );
    }

    public function test_filtrar_por_tipo_objetivo(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $denunciante = $this->crearUsuario();

        $publicacion = $this->crearReporte(
            $denunciante,
            1,
            'PUBLICACION'
        );

        $this->crearReporte(
            $denunciante,
            1,
            'PERFIL'
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->getJson(
                self::BASE_URL.
                '?tipo_objetivo=PUBLICACION'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            );

        $this->assertEquals(
            $publicacion->id_reporte,
            $response->json('data.0.id_reporte')
        );
    }

    public function test_consultar_reporte(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $denunciante = $this->crearUsuario();

        $reporte = $this->crearReporte(
            $denunciante,
            1,
            'PUBLICACION'
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->getJson(
                self::BASE_URL.
                '/'.
                $reporte->id_reporte
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Reporte obtenido correctamente.',
            ]);

        $this->assertEquals(
            $reporte->id_reporte,
            $response->json('data.id_reporte')
        );

        $this->assertEquals(
            'PUBLICACION',
            $response->json('data.tipo_objetivo')
        );
    }

    public function test_reporte_inexistente(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->getJson(
                self::BASE_URL.
                '/999999'
            );

        $response
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Reporte no encontrado.',
                'error_code' => 'RESOURCE_NOT_FOUND',
            ]);
    }

    public function test_marcar_en_revision(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $denunciante = $this->crearUsuario();

        $reporte = $this->crearReporte(
            $denunciante,
            1
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                self::BASE_URL.
                '/'.
                $reporte->id_reporte.
                '/revisar'
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Reporte marcado en revisión.',
            ]);

        $reporte->refresh();

        $this->assertEquals(
            2,
            $reporte->id_estado_reporte
        );

        $this->assertEquals(
            $admin->id_usuario,
            $reporte->id_admin_moderador
        );
    }

    public function test_resolver_reporte_pendiente(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $denunciante = $this->crearUsuario();

        $reporte = $this->crearReporte(
            $denunciante,
            1
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                self::BASE_URL.
                '/'.
                $reporte->id_reporte.
                '/resolver',
                [
                    'resolucion' => 'La revisión confirmó que el reporte procede.',
                ]
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Reporte resuelto correctamente.',
            ]);

        $reporte->refresh();

        $this->assertEquals(
            3,
            $reporte->id_estado_reporte
        );

        $this->assertEquals(
            $admin->id_usuario,
            $reporte->id_admin_moderador
        );

        $this->assertEquals(
            'La revisión confirmó que el reporte procede.',
            $reporte->resolucion
        );

        $this->assertNotNull(
            $reporte->fecha_resolucion
        );
    }

    public function test_resolver_reporte_en_revision(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $denunciante = $this->crearUsuario();

        $reporte = $this->crearReporte(
            $denunciante,
            2
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                self::BASE_URL.
                '/'.
                $reporte->id_reporte.
                '/resolver',
                [
                    'resolucion' => 'El caso fue revisado y concluido.',
                ]
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Reporte resuelto correctamente.',
            ]);

        $reporte->refresh();

        $this->assertEquals(
            3,
            $reporte->id_estado_reporte
        );

        $this->assertEquals(
            'El caso fue revisado y concluido.',
            $reporte->resolucion
        );

        $this->assertNotNull(
            $reporte->fecha_resolucion
        );
    }

    public function test_no_resolver_reporte_concluido(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $denunciante = $this->crearUsuario();

        $reporte = $this->crearReporte(
            $denunciante,
            3
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                self::BASE_URL.
                '/'.
                $reporte->id_reporte.
                '/resolver',
                [
                    'resolucion' => 'Intento de resolución duplicada.',
                ]
            );

        $response
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'El reporte ya fue resuelto.',
                'error_code' => 'REPORT_ALREADY_RESOLVED',
            ]);
    }

    public function test_validar_resolucion_obligatoria(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $denunciante = $this->crearUsuario();

        $reporte = $this->crearReporte(
            $denunciante,
            1
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                self::BASE_URL.
                '/'.
                $reporte->id_reporte.
                '/resolver',
                []
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'resolucion',
            ]);
    }

    public function test_filtro_estado_fuera_del_rango_y_estado_sin_resultados(): void
    {
        $admin = $this->crearUsuario(administrador: true);
        $token = $this->token($admin);

        $this->withToken($token)
            ->getJson(self::BASE_URL . '?id_estado=32768')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_estado']);

        $this->withToken($token)
            ->getJson(self::BASE_URL . '?id_estado=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_estado']);

        $this->withToken($token)
            ->getJson(self::BASE_URL . '?id_estado=3')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
