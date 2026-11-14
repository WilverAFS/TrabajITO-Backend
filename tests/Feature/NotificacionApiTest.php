<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\TipoNotificacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificacionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private User $otroUsuario;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['SOLICITUD', 'CONTRATACION', 'PUBLICACION', 'SISTEMA', 'REPORTE'] as $tipo) {
            TipoNotificacion::firstOrCreate(['tipo_notificacion' => $tipo]);
        }

        $this->usuario = $this->crearUsuario('notificaciones@test.com');
        $this->otroUsuario = $this->crearUsuario('otro-notificaciones@test.com');
    }

    private function crearUsuario(string $correo): User
    {
        return User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => $correo,
            'contrasena_hash' => 'Prueba123!',
        ]);
    }

    private function token(User $usuario): string
    {
        return $usuario->createToken('Pruebas API 26')->plainTextToken;
    }

    private function crearNotificacion(
        User $usuario,
        bool $leida = false
    ): Notificacion {
        $tipo = TipoNotificacion::query()
            ->where('tipo_notificacion', 'SISTEMA')
            ->firstOrFail();

        return Notificacion::create([
            'id_usuario' => $usuario->id_usuario,
            'id_tipo_notificacion' => $tipo->id_tipo_notificacion,
            'titulo' => 'Notificación de prueba',
            'mensaje' => 'Mensaje utilizado para probar la API 26.',
            'leida' => $leida,
            'fecha_creacion' => now(),
        ]);
    }

    public function test_todas_las_operaciones_requieren_autenticacion(): void
    {
        $this->getJson('/api/v1/notificaciones')->assertUnauthorized();
        $this->getJson('/api/v1/notificaciones/no-leidas/count')->assertUnauthorized();
        $this->patchJson('/api/v1/notificaciones/1/leer')->assertUnauthorized();
        $this->patchJson('/api/v1/notificaciones/leer-todas')->assertUnauthorized();
    }

    public function test_usuario_solo_consulta_sus_notificaciones(): void
    {
        $propia = $this->crearNotificacion($this->usuario);
        $this->crearNotificacion($this->otroUsuario);

        $this->withToken($this->token($this->usuario))
            ->getJson('/api/v1/notificaciones')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_notificacion', $propia->id_notificacion)
            ->assertJsonStructure([
                'data' => [[
                    'id_notificacion',
                    'tipo' => ['id_tipo_notificacion', 'tipo_notificacion'],
                    'titulo',
                    'mensaje',
                    'leida',
                    'fecha_creacion',
                ]],
                'meta' => ['current_page', 'per_page', 'last_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
    }

    public function test_lista_filtra_por_estado_y_respeta_paginacion(): void
    {
        $this->crearNotificacion($this->usuario, false);
        $this->crearNotificacion($this->usuario, false);
        $this->crearNotificacion($this->usuario, true);

        $this->withToken($this->token($this->usuario))
            ->getJson('/api/v1/notificaciones?leida=false&page=1&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.leida', false)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_consulta_conteo_de_notificaciones_no_leidas(): void
    {
        $this->crearNotificacion($this->usuario, false);
        $this->crearNotificacion($this->usuario, false);
        $this->crearNotificacion($this->usuario, true);
        $this->crearNotificacion($this->otroUsuario, false);

        $this->withToken($this->token($this->usuario))
            ->getJson('/api/v1/notificaciones/no-leidas/count')
            ->assertOk()
            ->assertJsonPath('data.total', 2);
    }

    public function test_usuario_puede_marcar_una_notificacion_como_leida(): void
    {
        $notificacion = $this->crearNotificacion($this->usuario);

        $this->withToken($this->token($this->usuario))
            ->patchJson("/api/v1/notificaciones/{$notificacion->id_notificacion}/leer")
            ->assertOk()
            ->assertJsonPath('message', 'Notificación marcada como leída.')
            ->assertJsonPath('data.leida', true);

        $this->assertDatabaseHas('notificaciones', [
            'id_notificacion' => $notificacion->id_notificacion,
            'id_usuario' => $this->usuario->id_usuario,
            'leida' => true,
        ]);
    }

    public function test_no_puede_marcar_notificacion_de_otro_usuario(): void
    {
        $ajena = $this->crearNotificacion($this->otroUsuario);

        $this->withToken($this->token($this->usuario))
            ->patchJson("/api/v1/notificaciones/{$ajena->id_notificacion}/leer")
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        $this->assertDatabaseHas('notificaciones', [
            'id_notificacion' => $ajena->id_notificacion,
            'leida' => false,
        ]);
    }

    public function test_usuario_puede_marcar_todas_sus_notificaciones_como_leidas(): void
    {
        $this->crearNotificacion($this->usuario, false);
        $this->crearNotificacion($this->usuario, false);
        $leida = $this->crearNotificacion($this->usuario, true);
        $ajena = $this->crearNotificacion($this->otroUsuario, false);

        $this->withToken($this->token($this->usuario))
            ->patchJson('/api/v1/notificaciones/leer-todas')
            ->assertOk()
            ->assertJsonPath('data.actualizadas', 2);

        $this->assertDatabaseMissing('notificaciones', [
            'id_usuario' => $this->usuario->id_usuario,
            'leida' => false,
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_notificacion' => $leida->id_notificacion,
            'leida' => true,
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_notificacion' => $ajena->id_notificacion,
            'leida' => false,
        ]);
    }

    public function test_rechaza_filtro_leida_invalido(): void
    {
        $this->withToken($this->token($this->usuario))
            ->getJson('/api/v1/notificaciones?leida=incorrecto')
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['leida']);
    }
}
