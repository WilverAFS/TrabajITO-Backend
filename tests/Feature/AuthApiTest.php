<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;
    private function crearUsuario(array $atributos = []): User
    {
        $correo = $atributos['correo']
            ?? 'usuario.' . bin2hex(random_bytes(6)) . '@example.test';

        $contrasena = $atributos['password'] ?? 'Prueba123!';

        $usuario = User::create([
            'nombre' => $atributos['nombre'] ?? 'Usuario',
            'apellido' => $atributos['apellido'] ?? 'Prueba',
            'correo' => $correo,
            'telefono' => $atributos['telefono'] ?? null,
            'contrasena_hash' => $contrasena,
        ]);

        $modificado = false;

        foreach (['activo', 'es_administrador'] as $campo) {
            if (array_key_exists($campo, $atributos)) {
                $usuario->{$campo} = $atributos[$campo];
                $modificado = true;
            }
        }

        if ($modificado) {
            $usuario->save();
        }

        return $usuario;
    }

    public function test_registro_crea_usuario_y_token_sin_datos_sensibles(): void
    {
        $correo = 'registro.' . bin2hex(random_bytes(6)) . '@example.test';

        $respuesta = $this->postJson('/api/v1/auth/register', [
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'correo' => $correo,
            'telefono' => null,
            'password' => 'Prueba123!',
            'password_confirmation' => 'Prueba123!',
        ]);

        $respuesta->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.usuario.correo', $correo)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'usuario' => [
                        'id_usuario',
                        'nombre',
                        'apellido',
                        'correo',
                        'telefono',
                        'es_administrador',
                        'activo',
                    ],
                    'token',
                ],
            ]);

        $datosUsuario = $respuesta->json('data.usuario');

        $this->assertArrayNotHasKey('contrasena_hash', $datosUsuario);
        $this->assertArrayNotHasKey('remember_token', $datosUsuario);
        $this->assertNotEmpty($respuesta->json('data.token'));

        $this->assertDatabaseHas('users', [
            'correo' => $correo,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_registro_rechaza_correo_duplicado(): void
    {
        $existente = $this->crearUsuario();

        $respuesta = $this->postJson('/api/v1/auth/register', [
            'nombre' => 'Otro',
            'apellido' => 'Usuario',
            'correo' => $existente->correo,
            'telefono' => null,
            'password' => 'Prueba123!',
            'password_confirmation' => 'Prueba123!',
        ]);

        $respuesta->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['correo']);
    }

    public function test_registro_rechaza_contrasenas_sin_confirmacion_correcta(): void
    {
        $respuesta = $this->postJson('/api/v1/auth/register', [
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'correo' => 'confirmacion.' . bin2hex(random_bytes(6)) . '@example.test',
            'password' => 'Prueba123!',
            'password_confirmation' => 'Otra123!',
        ]);

        $respuesta->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registro_rechaza_campos_obligatorios_ausentes(): void
    {
        $respuesta = $this->postJson('/api/v1/auth/register', []);

        $respuesta->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors([
                'nombre',
                'apellido',
                'correo',
                'password',
            ]);
    }

    public function test_login_correcto_genera_token(): void
    {
        $correo = 'login.' . bin2hex(random_bytes(6)) . '@example.test';

        $this->crearUsuario([
            'correo' => $correo,
            'password' => 'Prueba123!',
        ]);

        $respuesta = $this->postJson('/api/v1/auth/login', [
            'correo' => $correo,
            'password' => 'Prueba123!',
            'device_name' => 'Pruebas automatizadas',
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.usuario.correo', $correo)
            ->assertJsonStructure([
                'data' => [
                    'usuario' => [
                        'id_usuario',
                        'correo',
                        'tiene_perfil_profesional',
                        'es_trabajador_habilitado',
                    ],
                    'token',
                ],
            ]);

        $this->assertNotEmpty($respuesta->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rechaza_credenciales_incorrectas(): void
    {
        $usuario = $this->crearUsuario();

        $respuesta = $this->postJson('/api/v1/auth/login', [
            'correo' => $usuario->correo,
            'password' => 'Incorrecta123!',
            'device_name' => 'Pruebas automatizadas',
        ]);

        $respuesta->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'INVALID_CREDENTIALS');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rechaza_cuentas_inactivas(): void
    {
        $usuario = $this->crearUsuario([
            'activo' => false,
        ]);

        $respuesta = $this->postJson('/api/v1/auth/login', [
            'correo' => $usuario->correo,
            'password' => 'Prueba123!',
            'device_name' => 'Pruebas automatizadas',
        ]);

        $respuesta->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'ACCOUNT_INACTIVE');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_requiere_nombre_del_dispositivo(): void
    {
        $usuario = $this->crearUsuario();

        $respuesta = $this->postJson('/api/v1/auth/login', [
            'correo' => $usuario->correo,
            'password' => 'Prueba123!',
        ]);

        $respuesta->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['device_name']);
    }

    public function test_consulta_del_usuario_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_consulta_del_usuario_devuelve_datos_sin_secretos(): void
    {
        $usuario = $this->crearUsuario();

        $token = $usuario->createToken('Prueba automatizada')
            ->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/v1/me');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id_usuario', $usuario->id_usuario)
            ->assertJsonPath('data.correo', $usuario->correo)
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.tiene_perfil_profesional', false)
            ->assertJsonPath('data.es_trabajador_habilitado', false)
            ->assertJsonStructure([
                'data' => [
                    'telefono',
                    'id_trabajador',
                ],
            ]);

        $datosUsuario = $respuesta->json('data');

        $this->assertArrayNotHasKey('contrasena_hash', $datosUsuario);
        $this->assertArrayNotHasKey('remember_token', $datosUsuario);
    }

    public function test_logout_revoca_solo_el_token_actual(): void
    {
        $usuario = $this->crearUsuario();

        $tokenActual = $usuario->createToken('Sesion actual')
            ->plainTextToken;

        $otroToken = $usuario->createToken('Otra sesion')
            ->plainTextToken;

        $respuesta = $this->withToken($tokenActual)
            ->postJson('/api/v1/auth/logout');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Sesión cerrada correctamente.')
            ->assertJsonPath('data', null);

        $this->app['auth']->forgetGuards();

        $this->withToken($tokenActual)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();

        $this->app['auth']->forgetGuards();

        $this->withToken($otroToken)
            ->getJson('/api/v1/me')
            ->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}