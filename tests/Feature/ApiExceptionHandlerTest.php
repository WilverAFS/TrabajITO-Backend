<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiExceptionHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_retorna_401_para_usuario_no_autenticado(): void
    {
        $response = $this->getJson('/api/v1/admin/oficios');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'UNAUTHENTICATED',
                'errors' => null,
            ]);
    }

    public function test_retorna_403_para_usuario_no_administrador(): void
    {
        $usuario = User::findOrFail(2);

        $response = $this
            ->actingAs($usuario, 'sanctum')
            ->getJson('/api/v1/admin/oficios');

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'No tienes permisos para acceder a este recurso.',
                'error_code' => 'FORBIDDEN',
                'errors' => null,
            ]);
    }

    public function test_retorna_403_para_usuario_inactivo(): void
    {
        $usuario = User::findOrFail(2);

        $usuario->activo = false;
        $usuario->save();

        $response = $this
            ->actingAs($usuario, 'sanctum')
            ->getJson('/api/v1/admin/oficios');

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'La cuenta está inactiva.',
                'error_code' => 'ACCOUNT_INACTIVE',
                'errors' => null,
            ]);
    }

    public function test_retorna_404_para_ruta_inexistente(): void
    {
        $response = $this->getJson('/api/v1/ruta-inexistente');

        $response
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 'NOT_FOUND',
                'errors' => null,
            ]);
    }

    public function test_retorna_405_para_metodo_no_permitido(): void
    {
        $response = $this->getJson('/api/v1/auth/login');

        $response
            ->assertStatus(405)
            ->assertJson([
                'success' => false,
                'error_code' => 'METHOD_NOT_ALLOWED',
                'errors' => null,
            ]);
    }

    public function test_retorna_429_cuando_se_excede_el_limite_de_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'correo' => 'inexistente@trabajito.test',
                'password' => 'Password123!',
                'device_name' => 'PHPUnit',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'correo' => 'inexistente@trabajito.test',
            'password' => 'Password123!',
            'device_name' => 'PHPUnit',
        ]);

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error_code' => 'TOO_MANY_REQUESTS',
                'errors' => null,
            ]);
    }
}
