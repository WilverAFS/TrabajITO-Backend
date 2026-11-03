<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Oficio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilProfesionalApiTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(): User
    {
        return User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => 'perfil.' . bin2hex(random_bytes(6)) . '@example.test',
            'telefono' => null,
            'contrasena_hash' => 'Prueba123!',
        ]);
    }

    private function crearMunicipio(bool $activo = true): Municipio
    {
        $sufijo = bin2hex(random_bytes(4));

        $estado = Estado::create([
            'estado' => 'Estado ' . $sufijo,
            'activo' => true,
        ]);

        return Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Municipio ' . $sufijo,
            'activo' => $activo,
        ]);
    }

    private function crearOficio(bool $activo = true): Oficio
    {
        return Oficio::create([
            'nombre' => 'Oficio ' . bin2hex(random_bytes(4)),
            'descripcion' => 'Descripción de prueba',
            'activo' => $activo,
        ]);
    }

    private function crearToken(User $usuario): string
    {
        return $usuario->createToken('Pruebas automatizadas')
            ->plainTextToken;
    }

    private function crearPerfil(
        User $usuario,
        Municipio $municipio,
        array $oficios
    ) {
        $perfil = $usuario->perfilProfesional()->create([
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil profesional de prueba',
        ]);

        $perfil->oficios()->sync(
            array_map(
                fn ($oficio) => $oficio->id_oficio,
                $oficios
            )
        );

        return $perfil;
    }

    // 1. Crear perfil correctamente.
    public function test_crear_perfil_profesional_correctamente(): void
    {
        $usuario = $this->crearUsuario();
        $municipio = $this->crearMunicipio();

        $oficio1 = $this->crearOficio();
        $oficio2 = $this->crearOficio();

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/perfiles', [
                'id_municipio' => $municipio->id_municipio,
                'descripcion' => 'Electricista con experiencia.',
                'oficios' => [
                    $oficio1->id_oficio,
                    $oficio2->id_oficio,
                ],
            ]);

        $respuesta->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id_usuario', $usuario->id_usuario)
            ->assertJsonPath('data.descripcion', 'Electricista con experiencia.')
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath(
                'data.municipio.municipio',
                $municipio->municipio
            )
            ->assertJsonPath(
                'data.municipio.estado.estado',
                $municipio->estado->estado
            );

        $idTrabajador = $respuesta->json('data.id_trabajador');

        $this->assertDatabaseHas('perfiles_profesionales', [
            'id_trabajador' => $idTrabajador,
            'id_usuario' => $usuario->id_usuario,
            'id_municipio' => $municipio->id_municipio,
            'activo' => false,
        ]);

        $this->assertDatabaseHas('perfil_oficio', [
            'id_trabajador' => $idTrabajador,
            'id_oficio' => $oficio1->id_oficio,
        ]);

        $this->assertDatabaseHas('perfil_oficio', [
            'id_trabajador' => $idTrabajador,
            'id_oficio' => $oficio2->id_oficio,
        ]);
    }

    // 2. Rechazar creación sin autenticación.
    public function test_crear_perfil_requiere_autenticacion(): void
    {
        $this->postJson('/api/v1/perfiles', [])
            ->assertUnauthorized();
    }

    // 3. Rechazar un segundo perfil para el mismo usuario.
    public function test_rechazar_segundo_perfil_del_usuario(): void
    {
        $usuario = $this->crearUsuario();
        $municipio = $this->crearMunicipio();
        $oficio = $this->crearOficio();

        $this->crearPerfil($usuario, $municipio, [$oficio]);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/perfiles', [
                'id_municipio' => $municipio->id_municipio,
                'descripcion' => 'Segundo perfil',
                'oficios' => [$oficio->id_oficio],
            ]);

        $respuesta->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'error_code',
                'PROFILE_ALREADY_EXISTS'
            );

        $this->assertDatabaseCount('perfiles_profesionales', 1);
    }

    // 4. Rechazar municipio inactivo.
    public function test_rechazar_municipio_inactivo(): void
    {
        $usuario = $this->crearUsuario();
        $municipio = $this->crearMunicipio(false);
        $oficio = $this->crearOficio();

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/perfiles', [
                'id_municipio' => $municipio->id_municipio,
                'descripcion' => 'Perfil de prueba',
                'oficios' => [$oficio->id_oficio],
            ]);

        $respuesta->assertStatus(422)
            ->assertJsonValidationErrors(['id_municipio']);

        $this->assertDatabaseCount('perfiles_profesionales', 0);
    }

    // 5. Rechazar oficio inactivo.
    public function test_rechazar_oficio_inactivo(): void
    {
        $usuario = $this->crearUsuario();
        $municipio = $this->crearMunicipio();
        $oficio = $this->crearOficio(false);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/perfiles', [
                'id_municipio' => $municipio->id_municipio,
                'descripcion' => 'Perfil de prueba',
                'oficios' => [$oficio->id_oficio],
            ]);

        $respuesta->assertStatus(422)
            ->assertJsonValidationErrors(['oficios.0']);

        $this->assertDatabaseCount('perfiles_profesionales', 0);
    }

    // 6. Rechazar oficios repetidos.
    public function test_rechazar_oficios_duplicados(): void
    {
        $usuario = $this->crearUsuario();
        $municipio = $this->crearMunicipio();
        $oficio = $this->crearOficio();

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/perfiles', [
                'id_municipio' => $municipio->id_municipio,
                'descripcion' => 'Perfil de prueba',
                'oficios' => [
                    $oficio->id_oficio,
                    $oficio->id_oficio,
                ],
            ]);

        $respuesta->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('perfiles_profesionales', 0);
    }

    // 7. Consultar perfil propio.
    public function test_consultar_perfil_profesional_propio(): void
    {
        $usuario = $this->crearUsuario();
        $municipio = $this->crearMunicipio();
        $oficio = $this->crearOficio();

        $perfil = $this->crearPerfil(
            $usuario,
            $municipio,
            [$oficio]
        );

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->getJson('/api/v1/me/perfil');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.id_trabajador',
                $perfil->id_trabajador
            )
            ->assertJsonPath(
                'data.descripcion',
                'Perfil profesional de prueba'
            )
            ->assertJsonPath(
                'data.municipio.municipio',
                $municipio->municipio
            )
            ->assertJsonPath(
                'data.municipio.estado.estado',
                $municipio->estado->estado
            )
            ->assertJsonFragment([
                'id_oficio' => $oficio->id_oficio,
                'nombre' => $oficio->nombre,
            ]);
    }

    // 8. Rechazar consulta sin autenticación.
    public function test_consultar_perfil_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/me/perfil')
            ->assertUnauthorized();
    }

    // 9. Responder 404 cuando el usuario no tiene perfil.
    public function test_consultar_perfil_inexistente(): void
    {
        $usuario = $this->crearUsuario();

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->getJson('/api/v1/me/perfil');

        $respuesta->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'error_code',
                'PROFILE_NOT_FOUND'
            );
    }
}
