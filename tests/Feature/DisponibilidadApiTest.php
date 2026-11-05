<?php

namespace Tests\Feature;

use App\Models\Disponibilidad;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisponibilidadApiTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(): User
    {
        return User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => 'disponibilidad.' . bin2hex(random_bytes(6)) . '@example.test',
            'telefono' => null,
            'contrasena_hash' => 'Prueba123!',
        ]);
    }

    private function crearMunicipio(): Municipio
    {
        $sufijo = bin2hex(random_bytes(4));

        $estado = Estado::create([
            'estado' => 'Estado ' . $sufijo,
            'activo' => true,
        ]);

        return Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Municipio ' . $sufijo,
            'activo' => true,
        ]);
    }

    private function crearPerfil(User $usuario): PerfilProfesional
    {
        $municipio = $this->crearMunicipio();

        return $usuario->perfilProfesional()->create([
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil profesional de prueba.',
        ]);
    }

    private function crearDisponibilidad(
        PerfilProfesional $perfil,
        array $datos = []
    ): Disponibilidad {
        return $perfil->disponibilidades()->create(
            array_merge([
                'dia_semana' => 1,
                'hora_inicio' => '09:00:00',
                'hora_fin' => '12:00:00',
                'activo' => true,
            ], $datos)
        );
    }

    private function crearToken(User $usuario): string
    {
        return $usuario->createToken('Pruebas automatizadas')
            ->plainTextToken;
    }

    // 1. Consultar disponibilidades propias, ordenadas y sin mostrar las de otros trabajadores.
    public function test_consultar_disponibilidades_propias(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $this->crearDisponibilidad($perfil, [
            'dia_semana' => 2,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '14:00:00',
        ]);

        $this->crearDisponibilidad($perfil, [
            'dia_semana' => 1,
            'hora_inicio' => '11:00:00',
            'hora_fin' => '13:00:00',
        ]);

        $this->crearDisponibilidad($perfil, [
            'dia_semana' => 1,
            'hora_inicio' => '08:00:00',
            'hora_fin' => '10:00:00',
        ]);

        // Crear disponibilidad de otro trabajador.
        $otroUsuario = $this->crearUsuario();
        $otroPerfil = $this->crearPerfil($otroUsuario);

        $this->crearDisponibilidad($otroPerfil);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->getJson('/api/v1/me/perfil/disponibilidades');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Disponibilidades obtenidas correctamente.'
            )
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.dia_semana', 1)
            ->assertJsonPath('data.0.dia_nombre', 'Lunes')
            ->assertJsonPath('data.0.hora_inicio', '08:00:00')
            ->assertJsonPath('data.1.hora_inicio', '11:00:00')
            ->assertJsonPath('data.2.dia_nombre', 'Martes');
    }

    // 2. Las operaciones de disponibilidad requieren autenticación.
    public function test_disponibilidades_requieren_autenticacion(): void
    {
        $this->getJson('/api/v1/me/perfil/disponibilidades')
            ->assertUnauthorized();

        $this->postJson('/api/v1/me/perfil/disponibilidades', [])
            ->assertUnauthorized();

        $this->patchJson('/api/v1/me/perfil/disponibilidades/1', [])
            ->assertUnauthorized();

        $this->deleteJson('/api/v1/me/perfil/disponibilidades/1')
            ->assertUnauthorized();
    }

    // 3. Rechazar consulta cuando el usuario no tiene perfil profesional.
    public function test_consultar_disponibilidades_sin_perfil(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->getJson('/api/v1/me/perfil/disponibilidades');

        $respuesta->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'PROFILE_NOT_FOUND');
    }

    // 4. Registrar disponibilidad correctamente.
    public function test_registrar_disponibilidad_correctamente(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/me/perfil/disponibilidades', [
                'dia_semana' => 1,
                'hora_inicio' => '09:00',
                'hora_fin' => '13:00',
            ]);

        $respuesta->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Disponibilidad registrada correctamente.'
            )
            ->assertJsonPath('data.dia_semana', 1)
            ->assertJsonPath('data.dia_nombre', 'Lunes')
            ->assertJsonPath('data.hora_inicio', '09:00:00')
            ->assertJsonPath('data.hora_fin', '13:00:00')
            ->assertJsonPath('data.activo', true);

        $this->assertDatabaseHas('disponibilidades', [
            'id_trabajador' => $perfil->id_trabajador,
            'dia_semana' => 1,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '13:00:00',
            'activo' => true,
        ]);
    }

    // 5. Rechazar registro cuando el usuario no tiene perfil.
    public function test_registrar_disponibilidad_sin_perfil(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/me/perfil/disponibilidades', [
                'dia_semana' => 1,
                'hora_inicio' => '09:00',
                'hora_fin' => '13:00',
            ]);

        $respuesta->assertStatus(404)
            ->assertJsonPath('error_code', 'PROFILE_NOT_FOUND');

        $this->assertDatabaseCount('disponibilidades', 0);
    }

    // 6. Rechazar días fuera del rango permitido.
    public function test_rechazar_dia_semana_invalido(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);
        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/me/perfil/disponibilidades', [
                'dia_semana' => 8,
                'hora_inicio' => '09:00',
                'hora_fin' => '13:00',
            ]);

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors(['dia_semana']);

        $this->assertDatabaseCount('disponibilidades', 0);
    }

    // 7. Rechazar horarios cuya hora final no sea posterior a la inicial.
    public function test_rechazar_hora_fin_incorrecta(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);
        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/me/perfil/disponibilidades', [
                'dia_semana' => 1,
                'hora_inicio' => '13:00',
                'hora_fin' => '09:00',
            ]);

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors(['hora_fin']);

        $this->assertDatabaseCount('disponibilidades', 0);
    }

    // 8. Rechazar intervalos superpuestos.
    public function test_rechazar_disponibilidad_superpuesta(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $this->crearDisponibilidad($perfil, [
            'dia_semana' => 1,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '12:00:00',
        ]);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/me/perfil/disponibilidades', [
                'dia_semana' => 1,
                'hora_inicio' => '11:00',
                'hora_fin' => '14:00',
            ]);

        $respuesta->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath(
                'errors.hora_inicio.0',
                'El intervalo se superpone con otro horario registrado para ese día.'
            );

        $this->assertDatabaseCount('disponibilidades', 1);
    }

    // 9. Permitir intervalos consecutivos sin superposición.
    public function test_permitir_horarios_consecutivos(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $this->crearDisponibilidad($perfil, [
            'dia_semana' => 1,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '12:00:00',
        ]);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->postJson('/api/v1/me/perfil/disponibilidades', [
                'dia_semana' => 1,
                'hora_inicio' => '12:00',
                'hora_fin' => '15:00',
            ]);

        $respuesta->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('disponibilidades', 2);
    }

    // 10. Actualizar parcialmente una disponibilidad.
    public function test_actualizar_disponibilidad_parcialmente(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $disponibilidad = $this->crearDisponibilidad($perfil);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->patchJson(
                '/api/v1/me/perfil/disponibilidades/' .
                $disponibilidad->id_disponibilidad,
                [
                    'hora_inicio' => '08:00',
                ]
            );

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Disponibilidad actualizada correctamente.'
            )
            ->assertJsonPath('data.hora_inicio', '08:00:00')
            ->assertJsonPath('data.hora_fin', '12:00:00');

        $this->assertDatabaseHas('disponibilidades', [
            'id_disponibilidad' => $disponibilidad->id_disponibilidad,
            'hora_inicio' => '08:00:00',
            'hora_fin' => '12:00:00',
        ]);
    }

    // 11. Actualizar el estado activo de una disponibilidad.
    public function test_actualizar_estado_de_disponibilidad(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $disponibilidad = $this->crearDisponibilidad($perfil);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->patchJson(
                '/api/v1/me/perfil/disponibilidades/' .
                $disponibilidad->id_disponibilidad,
                [
                    'activo' => false,
                ]
            );

        $respuesta->assertOk()
            ->assertJsonPath('data.activo', false);

        $this->assertDatabaseHas('disponibilidades', [
            'id_disponibilidad' => $disponibilidad->id_disponibilidad,
            'activo' => false,
        ]);
    }

    // 12. Rechazar actualizaciones sin campos.
    public function test_actualizar_disponibilidad_requiere_campos(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $disponibilidad = $this->crearDisponibilidad($perfil);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->patchJson(
                '/api/v1/me/perfil/disponibilidades/' .
                $disponibilidad->id_disponibilidad,
                []
            );

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors(['disponibilidad']);
    }

    // 13. Rechazar actualizaciones que produzcan horarios incorrectos.
    public function test_rechazar_actualizacion_con_horas_incorrectas(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $disponibilidad = $this->crearDisponibilidad($perfil);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->patchJson(
                '/api/v1/me/perfil/disponibilidades/' .
                $disponibilidad->id_disponibilidad,
                [
                    'hora_inicio' => '13:00',
                ]
            );

        $respuesta->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => ['hora_fin'],
            ]);

        // El registro original debe permanecer sin cambios.
        $this->assertDatabaseHas('disponibilidades', [
            'id_disponibilidad' => $disponibilidad->id_disponibilidad,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '12:00:00',
        ]);
    }

    // 14. Rechazar actualizaciones que generen superposición.
    public function test_rechazar_actualizacion_superpuesta(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $primera = $this->crearDisponibilidad($perfil, [
            'hora_inicio' => '09:00:00',
            'hora_fin' => '12:00:00',
        ]);

        $segunda = $this->crearDisponibilidad($perfil, [
            'hora_inicio' => '13:00:00',
            'hora_fin' => '15:00:00',
        ]);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->patchJson(
                '/api/v1/me/perfil/disponibilidades/' .
                $primera->id_disponibilidad,
                [
                    'hora_fin' => '14:00',
                ]
            );

        $respuesta->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => ['hora_inicio'],
            ]);

        $this->assertDatabaseHas('disponibilidades', [
            'id_disponibilidad' => $primera->id_disponibilidad,
            'hora_fin' => '12:00:00',
        ]);

        $this->assertDatabaseHas('disponibilidades', [
            'id_disponibilidad' => $segunda->id_disponibilidad,
            'hora_fin' => '15:00:00',
        ]);
    }

    // 15. Impedir actualizar disponibilidades de otros trabajadores o inexistentes.
    public function test_no_permitir_actualizar_disponibilidad_de_otro_usuario(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $otroUsuario = $this->crearUsuario();
        $otroPerfil = $this->crearPerfil($otroUsuario);

        $disponibilidadAjena = $this->crearDisponibilidad(
            $otroPerfil
        );

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->patchJson(
                '/api/v1/me/perfil/disponibilidades/' .
                $disponibilidadAjena->id_disponibilidad,
                [
                    'hora_inicio' => '08:00',
                ]
            );

        $respuesta->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'error_code',
                'AVAILABILITY_NOT_FOUND'
            );

        // También comprobar un identificador inexistente.
        $this->withToken($token)
            ->patchJson(
                '/api/v1/me/perfil/disponibilidades/999999',
                [
                    'hora_inicio' => '08:00',
                ]
            )
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'AVAILABILITY_NOT_FOUND'
            );

        $this->assertDatabaseHas('disponibilidades', [
            'id_disponibilidad' => $disponibilidadAjena->id_disponibilidad,
            'hora_inicio' => '09:00:00',
        ]);
    }

    // 16. Eliminar disponibilidad correctamente.
    public function test_eliminar_disponibilidad_correctamente(): void
    {
        $usuario = $this->crearUsuario();
        $perfil = $this->crearPerfil($usuario);

        $disponibilidad = $this->crearDisponibilidad($perfil);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->deleteJson(
                '/api/v1/me/perfil/disponibilidades/' .
                $disponibilidad->id_disponibilidad
            );

        $respuesta->assertNoContent();

        $this->assertDatabaseMissing('disponibilidades', [
            'id_disponibilidad' => $disponibilidad->id_disponibilidad,
        ]);
    }

    // 17. Impedir eliminar disponibilidades de otros trabajadores.
    public function test_no_permitir_eliminar_disponibilidad_de_otro_usuario(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $otroUsuario = $this->crearUsuario();
        $otroPerfil = $this->crearPerfil($otroUsuario);

        $disponibilidadAjena = $this->crearDisponibilidad(
            $otroPerfil
        );

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->deleteJson(
                '/api/v1/me/perfil/disponibilidades/' .
                $disponibilidadAjena->id_disponibilidad
            );

        $respuesta->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'AVAILABILITY_NOT_FOUND'
            );

        $this->assertDatabaseHas('disponibilidades', [
            'id_disponibilidad' => $disponibilidadAjena->id_disponibilidad,
        ]);
    }

    // 18. Rechazar la eliminación de una disponibilidad inexistente.
    public function test_eliminar_disponibilidad_inexistente(): void
    {
        $usuario = $this->crearUsuario();
        $this->crearPerfil($usuario);

        $token = $this->crearToken($usuario);

        $respuesta = $this->withToken($token)
            ->deleteJson(
                '/api/v1/me/perfil/disponibilidades/999999'
            );

        $respuesta->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'error_code',
                'AVAILABILITY_NOT_FOUND'
            );
    }
}
