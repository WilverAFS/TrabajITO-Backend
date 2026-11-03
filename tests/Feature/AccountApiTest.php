<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(
        array $atributos = []
    ): User {

        $correo = $atributos['correo']
            ?? 'usuario.' . bin2hex(random_bytes(6)) . '@example.test';

        $contrasena = $atributos['password']
            ?? 'Prueba123!';

        $usuario = User::create([
            'nombre' => $atributos['nombre']
                ?? 'Usuario',
            'apellido' => $atributos['apellido']
                ?? 'Prueba',
            'correo' => $correo,
            'telefono' => $atributos['telefono']
                ?? null,
            'contrasena_hash' => $contrasena,
        ]);

        $modificado = false;

        foreach (['activo', 'es_administrador'] as $campo) {

            if (array_key_exists($campo, $atributos)) {
                $usuario->{$campo}
                    = $atributos[$campo];

                $modificado = true;
            }
        }

        if ($modificado) {
            $usuario->save();
        }

        return $usuario;
    }

    public function test_actualiza_los_datos_de_la_cuenta(): void
    {
        $usuario = $this->crearUsuario();

        $token = $usuario
            ->createToken('Prueba automatizada')
            ->plainTextToken;

        $respuesta = $this
            ->withToken($token)
            ->patchJson('/api/v1/me', [
                'nombre' => 'Juan',
                'apellido' => 'Actualizado',
                'telefono' => '9511234567',
            ]);

        $respuesta
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.nombre',
                'Juan'
            )
            ->assertJsonPath(
                'data.apellido',
                'Actualizado'
            )
            ->assertJsonPath(
                'data.nombre_completo',
                'Juan Actualizado'
            )
            ->assertJsonPath(
                'data.telefono',
                '9511234567'
            );

        $this->assertDatabaseHas('users', [
            'id_usuario' => $usuario->id_usuario,
            'nombre' => 'Juan',
            'apellido' => 'Actualizado',
            'telefono' => '9511234567',
        ]);
    }

    public function test_actualizacion_requiere_autenticacion(): void
    {
        $this->patchJson('/api/v1/me', [
            'nombre' => 'Juan',
        ])->assertUnauthorized();
    }

    public function test_rechaza_correo_duplicado(): void
    {
        $usuarioA = $this->crearUsuario();

        $usuarioB = $this->crearUsuario([
            'correo' => 'duplicado@example.test',
        ]);

        $token = $usuarioA
            ->createToken('Prueba automatizada')
            ->plainTextToken;

        $respuesta = $this
            ->withToken($token)
            ->patchJson('/api/v1/me', [
                'correo' => $usuarioB->correo,
            ]);

        $respuesta
            ->assertStatus(422)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'error_code',
                'VALIDATION_ERROR'
            )
            ->assertJsonValidationErrors([
                'correo',
            ]);
    }

    public function test_cambio_de_contrasena_exitoso(): void
    {
        $usuario = $this->crearUsuario([
            'password' => 'Prueba123!',
        ]);

        $token = $usuario
            ->createToken('Prueba automatizada')
            ->plainTextToken;

        $respuesta = $this
            ->withToken($token)
            ->patchJson('/api/v1/me/password', [
                'password_actual' => 'Prueba123!',
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ]);

        $respuesta
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            );

        $usuario->refresh();

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check(
                'NuevaClave123!',
                $usuario->contrasena_hash
            )
        );
    }

    public function test_rechaza_contrasena_actual_incorrecta(): void
    {
        $usuario = $this->crearUsuario();

        $token = $usuario
            ->createToken('Prueba automatizada')
            ->plainTextToken;

        $respuesta = $this
            ->withToken($token)
            ->patchJson('/api/v1/me/password', [
                'password_actual' => 'Incorrecta123!',
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ]);

        $respuesta
            ->assertStatus(422)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'error_code',
                'INVALID_CURRENT_PASSWORD'
            );
    }

    public function test_cambio_de_contrasena_requiere_autenticacion(): void
    {
        $this->patchJson('/api/v1/me/password', [
            'password_actual' => 'Prueba123!',
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])->assertUnauthorized();
    }

    public function test_solicita_recuperacion_de_contrasena(): void
    {
        $usuario = $this->crearUsuario([
            'correo' => 'recuperacion@example.test',
        ]);

        $respuesta = $this->postJson(
            '/api/v1/auth/forgot-password',
            [
                'correo' => $usuario->correo,
            ]
        );

        $respuesta
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertDatabaseHas(
            'password_reset_tokens',
            [
                'email' => $usuario->correo,
            ]
        );
    }

    public function test_rechaza_token_invalido(): void
    {
        $usuario = $this->crearUsuario([
            'correo' => 'reset@example.test',
        ]);

        $respuesta = $this->postJson(
            '/api/v1/auth/reset-password',
            [
                'correo' => $usuario->correo,
                'token' => 'token-inexistente',
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ]
        );

        $respuesta
            ->assertStatus(422)
            ->assertJsonPath(
                'success',
                false
            )
            ->assertJsonPath(
                'error_code',
                'INVALID_RESET_TOKEN'
            );
    }

    public function test_restablece_contrasena_con_token_valido(): void
    {
        $usuario = $this->crearUsuario([
            'correo' => 'restablecer@example.test',
        ]);

        $token = Password::createToken(
            $usuario
        );

        $tokenApi = $usuario
            ->createToken('Sesion previa')
            ->plainTextToken;

        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );

        $respuesta = $this->postJson(
            '/api/v1/auth/reset-password',
            [
                'correo' => $usuario->correo,
                'token' => $token,
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ]
        );

        $respuesta
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            );

        $usuario->refresh();

        $this->assertTrue(
            Hash::check(
                'NuevaClave123!',
                $usuario->contrasena_hash
            )
        );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }

    public function test_cambio_de_contrasena_revoca_todos_los_tokens(): void
    {
        $usuario = $this->crearUsuario();

        $tokenA = $usuario
            ->createToken('Sesion A')
            ->plainTextToken;

        $tokenB = $usuario
            ->createToken('Sesion B')
            ->plainTextToken;

        $this->assertDatabaseCount(
            'personal_access_tokens',
            2
        );

        $this->withToken($tokenA)
            ->patchJson('/api/v1/me/password', [
                'password_actual' => 'Prueba123!',
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ])
            ->assertOk();

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }

    public function test_rechaza_telefono_duplicado(): void
    {
        $usuarioA = $this->crearUsuario([
            'telefono' => '9511234567',
        ]);

        $usuarioB = $this->crearUsuario([
            'telefono' => '9517654321',
        ]);

        $token = $usuarioA
            ->createToken('Prueba')
            ->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/v1/me', [
                'telefono' => '9517654321',
            ])
            ->assertStatus(422);
    }

    public function test_rechaza_telefono_con_longitud_invalida(): void
    {
        $usuario = $this->crearUsuario();

        $token = $usuario
            ->createToken('Prueba')
            ->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/v1/me', [
                'telefono' => '123',
            ])
            ->assertStatus(422);
    }
}
