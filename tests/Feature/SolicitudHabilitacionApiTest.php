<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\EstadoSolicitud;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudHabilitacionApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/me/perfil/solicitudes-habilitacion';

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'Pendiente',
            'En revisión',
            'Aceptada',
            'Rechazada',
        ] as $nombre) {
            EstadoSolicitud::firstOrCreate([
                'estado_solicitud' => $nombre,
            ]);
        }
    }

    private function crearUsuario(): User
    {
        return User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => 'solicitud.' .
                bin2hex(random_bytes(6)) . '@example.test',
            'telefono' => null,
            'contrasena_hash' => 'Prueba123!',
        ]);
    }

    private function crearPerfil(
        User $usuario,
        bool $activo = false
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
            'descripcion' => 'Perfil profesional de prueba',
        ]);

        $perfil->activo = $activo;
        $perfil->save();

        return $perfil;
    }

    private function token(User $usuario): string
    {
        return $usuario->createToken('Pruebas API 18')
            ->plainTextToken;
    }

    private function crearSolicitud(
        PerfilProfesional $perfil,
        int $estado = 1
    ) {
        $solicitud = $perfil
            ->solicitudesHabilitacion()
            ->create([]);

        $solicitud->id_estado_solicitud = $estado;
        $solicitud->save();

        return $solicitud;
    }

    // 1. Las rutas requieren autenticación.
    public function test_requiere_autenticacion(): void
    {
        $this->postJson(self::URL, [])
            ->assertUnauthorized();

        $this->getJson(self::URL)
            ->assertUnauthorized();
    }

    // 2. El usuario debe tener perfil profesional.
    public function test_rechazar_usuario_sin_perfil(): void
    {
        $usuario = $this->crearUsuario();

        $this->withToken($this->token($usuario))
            ->postJson(self::URL, [])
            ->assertNotFound()
            ->assertJsonPath('error_code', 'PROFILE_NOT_FOUND');

        $this->getJson(self::URL)
            ->assertNotFound()
            ->assertJsonPath('error_code', 'PROFILE_NOT_FOUND');
    }

    // 3. Registrar solicitud correctamente.
    public function test_crear_solicitud_correctamente(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $respuesta = $this->withToken($this->token($usuario))
            ->postJson(self::URL, []);

        $respuesta->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.estado.id_estado_solicitud',
                1
            )
            ->assertJsonPath(
                'data.estado.estado_solicitud',
                'Pendiente'
            )
            ->assertJsonStructure([
                'data' => [
                    'id_solicitud',
                    'estado',
                    'fecha_solicitud',
                ],
            ]);

        $this->assertDatabaseHas('solicitudes_habilitacion', [
            'id_trabajador' => $perfil->id_trabajador,
            'id_estado_solicitud' => 1,
        ]);

        $this->assertDatabaseHas('perfiles_profesionales', [
            'id_trabajador' => $perfil->id_trabajador,
            'activo' => false,
        ]);
    }

    // 4. No solicitar si el perfil ya está habilitado.
    public function test_rechazar_perfil_habilitado(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario, true);

        $this->withToken($this->token($usuario))
            ->postJson(self::URL, [])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount(
            'solicitudes_habilitacion',
            0
        );
    }

    // 5. No permitir solicitudes pendientes duplicadas.
    public function test_rechazar_solicitud_duplicada(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $this->withToken($this->token($usuario))
            ->postJson(self::URL, [])
            ->assertCreated();

        $this->postJson(self::URL, [])
            ->assertStatus(409)
            ->assertJsonPath(
                'error_code',
                'PENDING_ENABLEMENT_REQUEST'
            );

        $this->assertDatabaseCount(
            'solicitudes_habilitacion',
            1
        );
    }

    // 6. No permitir otra solicitud si está en revisión.
    public function test_rechazar_solicitud_en_revision(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $this->crearSolicitud($perfil, 2);

        $this->withToken($this->token($usuario))
            ->postJson(self::URL, [])
            ->assertStatus(409)
            ->assertJsonPath(
                'error_code',
                'PENDING_ENABLEMENT_REQUEST'
            );

        $this->assertDatabaseCount(
            'solicitudes_habilitacion',
            1
        );
    }

    // 7. Permitir nueva solicitud después de un rechazo.
    public function test_permitir_reintento_tras_rechazo(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $this->crearSolicitud($perfil, 4);

        $this->withToken($this->token($usuario))
            ->postJson(self::URL, [])
            ->assertCreated()
            ->assertJsonPath(
                'data.estado.id_estado_solicitud',
                1
            );

        $this->assertDatabaseCount(
            'solicitudes_habilitacion',
            2
        );
    }

    // 8. Consultar solamente el historial propio.
    public function test_consultar_historial_propio(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $anterior = $this->crearSolicitud($perfil, 4);
        $reciente = $this->crearSolicitud($perfil, 1);

        $otroUsuario = $this->crearUsuario();
        $otroPerfil = $this->crearPerfil($otroUsuario);
        $ajena = $this->crearSolicitud($otroPerfil);

        $this->withToken($this->token($usuario))
            ->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath(
                'data.0.id_solicitud',
                $reciente->id_solicitud
            )
            ->assertJsonPath(
                'data.1.id_solicitud',
                $anterior->id_solicitud
            )
            ->assertJsonMissing([
                'id_solicitud' => $ajena->id_solicitud,
            ]);
    }

    // 9. Historial vacío cuando no existen solicitudes.
    public function test_historial_vacio(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $this->withToken($this->token($usuario))
            ->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
