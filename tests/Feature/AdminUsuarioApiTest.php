<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\EstadoSolicitud;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\SolicitudHabilitacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsuarioApiTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;
    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Pendiente', 'En revisión', 'Aceptada', 'Rechazada'] as $estado) {
            EstadoSolicitud::firstOrCreate(['estado_solicitud' => $estado]);
        }

        $this->administrador = $this->crearUsuario('admin@test.com', true, true);
        $this->cliente = $this->crearUsuario('cliente@test.com');
    }

    private function crearUsuario(
        string $correo,
        bool $administrador = false,
        bool $activo = true
    ): User {
        $usuario = User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => $correo,
            'contrasena_hash' => 'Prueba123!',
        ]);

        $usuario->es_administrador = $administrador;
        $usuario->activo = $activo;
        $usuario->save();

        return $usuario;
    }

    private function token(User $usuario): string
    {
        return $usuario->createToken('Pruebas API 27')->plainTextToken;
    }

    private function crearPerfil(User $usuario, bool $activo = true): PerfilProfesional
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

        $perfil = PerfilProfesional::create([
            'id_usuario' => $usuario->id_usuario,
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil de prueba para administración.',
        ]);
        $perfil->activo = $activo;
        $perfil->save();

        return $perfil;
    }

    private function crearSolicitudAceptada(PerfilProfesional $perfil): void
    {
        $estado = EstadoSolicitud::query()
            ->where('estado_solicitud', 'Aceptada')
            ->firstOrFail();

        $solicitud = SolicitudHabilitacion::create([
            'id_trabajador' => $perfil->id_trabajador,
        ]);
        $solicitud->id_estado_solicitud = $estado->id_estado_solicitud;
        $solicitud->save();
    }

    public function test_endpoints_administrativos_requieren_autenticacion(): void
    {
        $this->getJson('/api/v1/admin/usuarios')->assertUnauthorized();
        $this->getJson('/api/v1/admin/usuarios/1')->assertUnauthorized();
        $this->patchJson('/api/v1/admin/usuarios/1/suspender')->assertUnauthorized();
        $this->patchJson('/api/v1/admin/usuarios/1/reactivar')->assertUnauthorized();
    }

    public function test_usuario_no_administrador_recibe_forbidden(): void
    {
        $this->withToken($this->token($this->cliente))
            ->getJson('/api/v1/admin/usuarios')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');
    }

    public function test_administrador_inactivo_no_puede_acceder(): void
    {
        $inactivo = $this->crearUsuario('admin-inactivo@test.com', true, false);

        $this->withToken($this->token($inactivo))
            ->getJson('/api/v1/admin/usuarios')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'ACCOUNT_INACTIVE');
    }

    public function test_administrador_puede_listar_y_filtrar_usuarios(): void
    {
        $trabajador = $this->crearUsuario('carlos.martinez@test.com');
        $perfil = $this->crearPerfil($trabajador, true);
        $this->crearSolicitudAceptada($perfil);
        $this->crearUsuario('otro.usuario@test.com');

        $this->withToken($this->token($this->administrador))
            ->getJson('/api/v1/admin/usuarios?buscar=carlos&activo=true&es_administrador=false&page=1&per_page=15')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.correo', 'carlos.martinez@test.com')
            ->assertJsonPath('data.0.tiene_perfil_profesional', true)
            ->assertJsonPath('data.0.es_trabajador_habilitado', true)
            ->assertJsonPath('meta.per_page', 15);
    }

    public function test_administrador_puede_consultar_detalle_con_perfil(): void
    {
        $trabajador = $this->crearUsuario('detalle-trabajador@test.com');
        $perfil = $this->crearPerfil($trabajador, true);
        $this->crearSolicitudAceptada($perfil);

        $this->withToken($this->token($this->administrador))
            ->getJson("/api/v1/admin/usuarios/{$trabajador->id_usuario}")
            ->assertOk()
            ->assertJsonPath('data.id_usuario', $trabajador->id_usuario)
            ->assertJsonPath('data.perfil_profesional.id_trabajador', $perfil->id_trabajador)
            ->assertJsonPath('data.perfil_profesional.activo', true)
            ->assertJsonPath('data.es_trabajador_habilitado', true)
            ->assertJsonMissingPath('data.contrasena_hash')
            ->assertJsonMissingPath('data.remember_token');
    }

    public function test_consultar_usuario_inexistente_devuelve_resource_not_found(): void
    {
        $this->withToken($this->token($this->administrador))
            ->getJson('/api/v1/admin/usuarios/999999')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }

    public function test_suspender_usuario_tambien_suspende_su_perfil(): void
    {
        $trabajador = $this->crearUsuario('suspender@test.com');
        $perfil = $this->crearPerfil($trabajador, true);

        $this->withToken($this->token($this->administrador))
            ->patchJson("/api/v1/admin/usuarios/{$trabajador->id_usuario}/suspender")
            ->assertOk()
            ->assertJsonPath('data.id_usuario', $trabajador->id_usuario)
            ->assertJsonPath('data.activo', false)
            ->assertJsonMissingPath('data.perfil_profesional');

        $this->assertDatabaseHas('users', [
            'id_usuario' => $trabajador->id_usuario,
            'activo' => false,
        ]);
        $this->assertDatabaseHas('perfiles_profesionales', [
            'id_trabajador' => $perfil->id_trabajador,
            'activo' => false,
        ]);
    }

    public function test_reactivar_usuario_no_reactiva_su_perfil_profesional(): void
    {
        $trabajador = $this->crearUsuario('reactivar@test.com', false, false);
        $perfil = $this->crearPerfil($trabajador, false);

        $this->withToken($this->token($this->administrador))
            ->patchJson("/api/v1/admin/usuarios/{$trabajador->id_usuario}/reactivar")
            ->assertOk()
            ->assertJsonPath('data.id_usuario', $trabajador->id_usuario)
            ->assertJsonPath('data.activo', true)
            ->assertJsonMissingPath('data.perfil_profesional');

        $this->assertDatabaseHas('users', [
            'id_usuario' => $trabajador->id_usuario,
            'activo' => true,
        ]);
        $this->assertDatabaseHas('perfiles_profesionales', [
            'id_trabajador' => $perfil->id_trabajador,
            'activo' => false,
        ]);
    }

    public function test_suspender_usuario_inexistente_devuelve_resource_not_found(): void
    {
        $this->withToken($this->token($this->administrador))
            ->patchJson('/api/v1/admin/usuarios/999999/suspender')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }
}
