<?php

namespace Tests\Feature;

use App\Models\Contratacion;
use App\Models\Estado;
use App\Models\EstadoContratacion;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContratacionApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/contrataciones';

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
        bool $activo = true,
        bool $administrador = false
    ): User {
        $usuario = User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => 'contratacion.' .
                bin2hex(random_bytes(6)) . '@example.test',
            'telefono' => null,
            'contrasena_hash' => 'Prueba123!',
        ]);

        $usuario->activo = $activo;
        $usuario->es_administrador = $administrador;
        $usuario->save();

        return $usuario;
    }

    private function crearPerfil(
        User $usuario,
        bool $activo = true
    ): PerfilProfesional {
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

        $perfil = $usuario->perfilProfesional()->create([
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Profesional de prueba',
        ]);

        $perfil->activo = $activo;
        $perfil->save();

        return $perfil;
    }

    private function token(User $usuario): string
    {
        return $usuario->createToken('Pruebas API 20')
            ->plainTextToken;
    }

    private function crearContratacion(
        User $cliente,
        PerfilProfesional $trabajador,
        int $estado = 1
    ): Contratacion {
        return Contratacion::create([
            'id_cliente' => $cliente->id_usuario,
            'id_trabajador' => $trabajador->id_trabajador,
            'id_estado_contratacion' => $estado,
            'descripcion_servicio' => 'Servicio de prueba',
        ]);
    }

    // 1. Crear contratación requiere autenticación.
    public function test_no_permite_crear_contratacion_sin_token(): void
    {
        $this->postJson(self::URL, [
            'id_trabajador' => 1,
            'descripcion_servicio' => 'Servicio de prueba',
        ])->assertUnauthorized();
    }

    // 2. Listar contrataciones requiere autenticación.
    public function test_no_permite_listar_contrataciones_sin_token(): void
    {
        $this->getJson(self::URL)
            ->assertUnauthorized();
    }

    // 3. Consultar detalle requiere autenticación.
    public function test_no_permite_ver_detalle_sin_token(): void
    {
        $this->getJson(self::URL . '/1')
            ->assertUnauthorized();
    }

    // 4. Crear contratación correctamente.
    public function test_crea_contratacion_en_estado_pendiente(): void
    {
        $cliente = $this->crearUsuario();
        $usuarioTrabajador = $this->crearUsuario();
        $trabajador = $this->crearPerfil($usuarioTrabajador);

        $respuesta = $this->withToken($this->token($cliente))
            ->postJson(self::URL, [
                'id_trabajador' => $trabajador->id_trabajador,
                'descripcion_servicio' => 'Reparar instalación eléctrica',
            ]);

        $respuesta->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.descripcion_servicio',
                'Reparar instalación eléctrica'
            )
            ->assertJsonPath(
                'data.estado.estado_contratacion',
                'Pendiente'
            );

        $this->assertDatabaseHas('contrataciones', [
            'id_cliente' => $cliente->id_usuario,
            'id_trabajador' => $trabajador->id_trabajador,
            'id_estado_contratacion' => 1,
            'descripcion_servicio' => 'Reparar instalación eléctrica',
        ]);
    }

    // 5. Los campos obligatorios deben validarse.
    public function test_valida_campos_obligatorios(): void
    {
        $cliente = $this->crearUsuario();

        $this->withToken($this->token($cliente))
            ->postJson(self::URL, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'id_trabajador',
                'descripcion_servicio',
            ]);
    }

    // 6. No permite trabajadores inexistentes.
    public function test_rechaza_trabajador_inexistente(): void
    {
        $cliente = $this->crearUsuario();

        $this->withToken($this->token($cliente))
            ->postJson(self::URL, [
                'id_trabajador' => 999999,
                'descripcion_servicio' => 'Servicio de prueba',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_trabajador');
    }

    // 7. No permite contratar perfiles inactivos.
    public function test_rechaza_perfil_inactivo(): void
    {
        $cliente = $this->crearUsuario();
        $usuarioTrabajador = $this->crearUsuario();
        $trabajador = $this->crearPerfil(
            $usuarioTrabajador,
            false
        );

        $this->withToken($this->token($cliente))
            ->postJson(self::URL, [
                'id_trabajador' => $trabajador->id_trabajador,
                'descripcion_servicio' => 'Servicio de prueba',
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'error_code',
                'WORKER_NOT_AVAILABLE'
            );
    }

    // 8. No permite contratar usuarios inactivos.
    public function test_rechaza_cuenta_inactiva(): void
    {
        $cliente = $this->crearUsuario();
        $usuarioTrabajador = $this->crearUsuario(false);
        $trabajador = $this->crearPerfil(
            $usuarioTrabajador,
            true
        );

        $this->withToken($this->token($cliente))
            ->postJson(self::URL, [
                'id_trabajador' => $trabajador->id_trabajador,
                'descripcion_servicio' => 'Servicio de prueba',
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'error_code',
                'WORKER_NOT_AVAILABLE'
            );
    }

    // 9. No permite contratar el perfil propio.
    public function test_no_permite_autocontratacion(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $this->withToken($this->token($usuario))
            ->postJson(self::URL, [
                'id_trabajador' => $perfil->id_trabajador,
                'descripcion_servicio' => 'Servicio propio',
            ])
            ->assertStatus(409)
            ->assertJsonPath(
                'error_code',
                'SELF_HIRING_NOT_ALLOWED'
            );
    }

    // 10. El listado solamente devuelve contratos propios.
    public function test_lista_solo_contrataciones_propias(): void
    {
        $cliente = $this->crearUsuario();
        $otroCliente = $this->crearUsuario();
        $usuarioTrabajador = $this->crearUsuario();
        $trabajador = $this->crearPerfil($usuarioTrabajador);

        $propia = $this->crearContratacion(
            $cliente,
            $trabajador
        );

        $this->crearContratacion(
            $otroCliente,
            $trabajador
        );

        $this->withToken($this->token($cliente))
            ->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.id_contratacion',
                $propia->id_contratacion
            )
            ->assertJsonPath(
                'data.0.acciones_disponibles.0',
                'cancelar'
            );
    }

    // 11. Filtros por participación y estado.
    public function test_filtra_por_participacion_y_estado(): void
    {
        $usuario = $this->crearUsuario();
        $otroUsuario = $this->crearUsuario();
        $tercerUsuario = $this->crearUsuario();

        $perfilUsuario = $this->crearPerfil($usuario);
        $perfilOtro = $this->crearPerfil($otroUsuario);

        $comoCliente = $this->crearContratacion(
            $usuario,
            $perfilOtro,
            1
        );

        $comoTrabajador = $this->crearContratacion(
            $tercerUsuario,
            $perfilUsuario,
            2
        );

        $this->withToken($this->token($usuario))
            ->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson(self::URL . '?participacion=cliente')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.id_contratacion',
                $comoCliente->id_contratacion
            );

        $this->getJson(self::URL . '?participacion=trabajador')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.id_contratacion',
                $comoTrabajador->id_contratacion
            );

        $this->getJson(self::URL . '?id_estado=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.id_contratacion',
                $comoCliente->id_contratacion
            );
    }

    // 12. Verificar paginación.
    public function test_paginacion_respeta_page_y_per_page(): void
    {
        $cliente = $this->crearUsuario();
        $usuarioTrabajador = $this->crearUsuario();
        $trabajador = $this->crearPerfil($usuarioTrabajador);

        $this->crearContratacion($cliente, $trabajador);
        $this->crearContratacion($cliente, $trabajador);
        $this->crearContratacion($cliente, $trabajador);

        $this->withToken($this->token($cliente))
            ->getJson(self::URL . '?page=2&per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(1, 'data');
    }

    // 13. El cliente puede ver su contratación.
    public function test_cliente_puede_ver_detalle(): void
    {
        $cliente = $this->crearUsuario();
        $usuarioTrabajador = $this->crearUsuario();
        $trabajador = $this->crearPerfil($usuarioTrabajador);

        $contratacion = $this->crearContratacion(
            $cliente,
            $trabajador
        );

        $this->withToken($this->token($cliente))
            ->getJson(
                self::URL . '/' . $contratacion->id_contratacion
            )
            ->assertOk()
            ->assertJsonPath(
                'data.id_contratacion',
                $contratacion->id_contratacion
            )
            ->assertJsonPath(
                'data.acciones_disponibles.0',
                'cancelar'
            )
            ->assertJsonPath('data.publicacion', null);
    }

    // 14. El trabajador puede consultar la contratación.
    public function test_trabajador_puede_ver_detalle(): void
    {
        $cliente = $this->crearUsuario();
        $usuarioTrabajador = $this->crearUsuario();
        $trabajador = $this->crearPerfil($usuarioTrabajador);

        $contratacion = $this->crearContratacion(
            $cliente,
            $trabajador
        );

        $this->withToken($this->token($usuarioTrabajador))
            ->getJson(
                self::URL . '/' . $contratacion->id_contratacion
            )
            ->assertOk()
            ->assertJsonPath(
                'data.acciones_disponibles.0',
                'aceptar'
            )
            ->assertJsonPath(
                'data.acciones_disponibles.1',
                'rechazar'
            );
    }

    // 15. El administrador puede ver contrataciones.
    public function test_administrador_puede_ver_detalle(): void
    {
        $administrador = $this->crearUsuario(true, true);
        $cliente = $this->crearUsuario();
        $usuarioTrabajador = $this->crearUsuario();
        $trabajador = $this->crearPerfil($usuarioTrabajador);

        $contratacion = $this->crearContratacion(
            $cliente,
            $trabajador
        );

        $this->withToken($this->token($administrador))
            ->getJson(
                self::URL . '/' . $contratacion->id_contratacion
            )
            ->assertOk()
            ->assertJsonPath(
                'data.id_contratacion',
                $contratacion->id_contratacion
            );
    }

    // 16. Los usuarios ajenos no pueden ver contratos.
    public function test_usuario_ajeno_no_puede_ver_detalle(): void
    {
        $cliente = $this->crearUsuario();
        $usuarioTrabajador = $this->crearUsuario();
        $usuarioAjeno = $this->crearUsuario();

        $trabajador = $this->crearPerfil($usuarioTrabajador);

        $contratacion = $this->crearContratacion(
            $cliente,
            $trabajador
        );

        $this->withToken($this->token($usuarioAjeno))
            ->getJson(
                self::URL . '/' . $contratacion->id_contratacion
            )
            ->assertForbidden()
            ->assertJsonPath(
                'error_code',
                'HIRING_ACCESS_DENIED'
            );
    }

    // 17. Una contratación inexistente devuelve 404.
    public function test_detalle_inexistente_devuelve_404(): void
    {
        $usuario = $this->crearUsuario();

        $this->withToken($this->token($usuario))
            ->getJson(self::URL . '/999999')
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'HIRING_NOT_FOUND'
            );
    }

    // 18. Los filtros inválidos devuelven 422.
    public function test_filtros_invalidos_devuelven_422(): void
    {
        $usuario = $this->crearUsuario();

        $this->withToken($this->token($usuario))
            ->getJson(
                self::URL . '?participacion=desconocido'
            )
            ->assertUnprocessable();
    }
}
