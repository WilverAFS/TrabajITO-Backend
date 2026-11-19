<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\BienvenidaUsuario;
use App\Notifications\CambioContrasena;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CorreoUsuariosTest extends TestCase
{
    use DatabaseMigrations;

    private function crearUsuario(): User
    {
        return User::create([
            'nombre' => 'Luis',
            'apellido' => 'Prueba',
            'correo' => 'correo.' . bin2hex(random_bytes(6)) . '@example.test',
            'contrasena_hash' => 'Prueba123!',
        ]);
    }

    public function test_registro_envia_correo_de_bienvenida(): void
    {
        Notification::fake();

        $correo = 'registro.' . bin2hex(random_bytes(6)) . '@example.test';

        $this->postJson('/api/v1/auth/register', [
            'nombre' => 'Luis',
            'apellido' => 'Prueba',
            'correo' => $correo,
            'password' => 'Prueba123!',
            'password_confirmation' => 'Prueba123!',
        ])->assertCreated();

        $usuario = User::where('correo', $correo)->firstOrFail();

        Notification::assertSentTo(
            $usuario,
            BienvenidaUsuario::class
        );
    }

    public function test_cambio_de_contrasena_envia_aviso(): void
    {
        Notification::fake();

        $usuario = $this->crearUsuario();
        $token = $usuario->createToken('Prueba correo')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/v1/me/password', [
                'password_actual' => 'Prueba123!',
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ])->assertOk();

        Notification::assertSentTo(
            $usuario,
            CambioContrasena::class
        );
    }

    public function test_recuperacion_de_contrasena_envia_aviso(): void
    {
        Notification::fake();

        $usuario = $this->crearUsuario();
        $token = Password::createToken($usuario);

        $this->postJson('/api/v1/auth/reset-password', [
            'correo' => $usuario->correo,
            'token' => $token,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])->assertOk();

        Notification::assertSentTo(
            $usuario,
            CambioContrasena::class
        );
    }

    public function test_actualizar_nombre_no_envia_aviso_de_contrasena(): void
    {
        Notification::fake();

        $usuario = $this->crearUsuario();
        $usuario->update(['nombre' => 'Mario']);

        Notification::assertNotSentTo(
            $usuario,
            CambioContrasena::class
        );
    }

public function test_recuperacion_envia_enlace_al_frontend(): void
{
    Notification::fake();

    $usuario = $this->crearUsuario();

    $this->postJson('/api/v1/auth/forgot-password', [
        'correo' => $usuario->correo,
    ])->assertOk();

    Notification::assertSentTo(
        $usuario,
        \Illuminate\Auth\Notifications\ResetPassword::class,
        function ($notificacion) use ($usuario): bool {
            $url = $notificacion->toMail($usuario)->actionUrl;

            $ruta = parse_url($url, PHP_URL_PATH);
            parse_str(
                (string) parse_url($url, PHP_URL_QUERY),
                $parametros
            );

            return $ruta === '/restablecer-contrasena'
                && ($parametros['correo'] ?? null) === $usuario->correo
                && ($parametros['token'] ?? null) === $notificacion->token;
        }
    );
   }

}
