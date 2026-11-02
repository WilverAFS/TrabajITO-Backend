<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Oficio;
use App\Models\PerfilProfesional;
use App\Models\TipoPublicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoApiTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(): User
    {
        return User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => 'usuario.' . bin2hex(random_bytes(6)) . '@example.test',
            'telefono' => null,
            'contrasena_hash' => 'Prueba123!',
        ]);
    }

    public function test_estados_devuelve_solo_los_activos(): void
    {
        Estado::create([
            'estado' => 'Oaxaca',
            'activo' => true,
        ]);

        Estado::create([
            'estado' => 'Estado inactivo',
            'activo' => false,
        ]);

        $respuesta = $this->getJson('/api/v1/estados');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['estado' => 'Oaxaca'])
            ->assertJsonMissing(['estado' => 'Estado inactivo']);
    }

    public function test_municipios_devuelve_solo_los_activos_del_estado(): void
    {
        $estado = Estado::create([
            'estado' => 'Oaxaca',
            'activo' => true,
        ]);

        Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Ocotlán de Morelos',
            'activo' => true,
        ]);

        Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Municipio inactivo',
            'activo' => false,
        ]);

        $respuesta = $this->getJson(
            "/api/v1/estados/{$estado->id_estado}/municipios"
        );

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'municipio' => 'Ocotlán de Morelos',
            ])
            ->assertJsonMissing([
                'municipio' => 'Municipio inactivo',
            ]);
    }

    public function test_oficios_devuelve_solo_los_activos(): void
    {
        Oficio::create([
            'nombre' => 'Carpintero',
            'descripcion' => 'Trabajo de carpintería',
            'activo' => true,
        ]);

        Oficio::create([
            'nombre' => 'Oficio inactivo',
            'descripcion' => 'No debe aparecer',
            'activo' => false,
        ]);

        $respuesta = $this->getJson('/api/v1/oficios');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['nombre' => 'Carpintero'])
            ->assertJsonMissing(['nombre' => 'Oficio inactivo']);
    }

    public function test_oficios_permite_buscar_por_nombre(): void
    {
        Oficio::create([
            'nombre' => 'Carpintero',
            'descripcion' => 'Trabajo de carpintería',
            'activo' => true,
        ]);

        Oficio::create([
            'nombre' => 'Electricista',
            'descripcion' => 'Instalaciones eléctricas',
            'activo' => true,
        ]);

        $respuesta = $this->getJson('/api/v1/oficios?buscar=carpin');

        $respuesta->assertOk()
            ->assertJsonFragment(['nombre' => 'Carpintero'])
            ->assertJsonMissing(['nombre' => 'Electricista']);
    }

    public function test_tipos_publicacion_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/tipos-publicacion')
            ->assertUnauthorized();
    }

    public function test_tipos_publicacion_rechaza_usuario_sin_perfil_habilitado(): void
    {
        $usuario = $this->crearUsuario();

        $token = $usuario->createToken('Prueba automatizada')
            ->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/v1/tipos-publicacion');

        $respuesta->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'PROFILE_NOT_ENABLED')
            ->assertJsonPath('errors', null);
    }

    public function test_tipos_publicacion_permite_usuario_con_perfil_habilitado(): void
    {
        $usuario = $this->crearUsuario();

        $estado = Estado::create([
            'estado' => 'Oaxaca',
            'activo' => true,
        ]);

        $municipio = Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Ocotlán de Morelos',
            'activo' => true,
        ]);

        $perfil = PerfilProfesional::create([
    'id_usuario' => $usuario->id_usuario,
    'id_municipio' => $municipio->id_municipio,
    'descripcion' => 'Perfil profesional de prueba',
]);

$perfil->activo = true;
$perfil->save();

        TipoPublicacion::create([
            'tipo_publicacion' => 'Servicio',
        ]);

        $token = $usuario->createToken('Prueba automatizada')
            ->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/v1/tipos-publicacion');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'tipo_publicacion' => 'Servicio',
            ]);
    }
}
