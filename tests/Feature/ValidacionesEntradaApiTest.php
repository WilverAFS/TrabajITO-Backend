<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Oficio;
use App\Models\PerfilProfesional;
use App\Models\TipoPublicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ValidacionesEntradaApiTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(
        string $password = 'Prueba123!',
        bool $administrador = false
    ): User {
        $usuario = User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => 'validacion.' . bin2hex(random_bytes(6)) . '@example.test',
            'telefono' => null,
            'contrasena_hash' => $password,
        ]);

        if ($administrador) {
            $usuario->es_administrador = true;
            $usuario->save();
        }

        return $usuario->fresh();
    }

    private function token(User $usuario): string
    {
        return $usuario->createToken('Pruebas de validación')->plainTextToken;
    }

    private function crearCatalogos(): array
    {
        $estado = Estado::create([
            'estado' => 'Oaxaca',
            'activo' => true,
        ]);

        $municipio = Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Oaxaca de Juárez',
            'activo' => true,
        ]);

        $oficio = Oficio::create([
            'nombre' => 'Electricidad',
            'descripcion' => 'Instalaciones y reparaciones eléctricas.',
            'activo' => true,
        ]);

        return [$estado, $municipio, $oficio];
    }

    public function test_registro_rechaza_nombres_con_numeros(): void
    {
        $correo = 'nombre.' . bin2hex(random_bytes(5)) . '@example.test';

        $this->postJson('/api/v1/auth/register', [
            'nombre' => 'Marvin123',
            'apellido' => 'Cruz López',
            'correo' => $correo,
            'password' => 'Prueba123!',
            'password_confirmation' => 'Prueba123!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);

        $this->assertDatabaseMissing('users', ['correo' => $correo]);
    }

    public function test_registro_rechaza_simbolos_en_nombres(): void
    {
        $correo = 'simbolo.' . bin2hex(random_bytes(5)) . '@example.test';

        $this->postJson('/api/v1/auth/register', [
            'nombre' => 'María-José',
            'apellido' => 'Cruz López',
            'correo' => $correo,
            'password' => 'Prueba123!',
            'password_confirmation' => 'Prueba123!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);

        $this->assertDatabaseMissing('users', ['correo' => $correo]);
    }

    public function test_registro_rechaza_correo_duplicado_sin_distinguir_mayusculas(): void
    {
        User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Existente',
            'correo' => 'Cuenta.Legada@Example.test',
            'contrasena_hash' => 'Prueba123!',
        ]);

        $this->postJson('/api/v1/auth/register', [
            'nombre' => 'María',
            'apellido' => 'López',
            'correo' => 'cuenta.legada@example.test',
            'password' => 'Prueba123!',
            'password_confirmation' => 'Prueba123!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['correo']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registro_acepta_acentos_y_espacios(): void
    {
        $correo = 'acentos.' . bin2hex(random_bytes(5)) . '@example.test';

        $this->postJson('/api/v1/auth/register', [
            'nombre' => 'José María',
            'apellido' => 'Cruz López',
            'correo' => $correo,
            'password' => 'Prueba123!',
            'password_confirmation' => 'Prueba123!',
        ])->assertCreated()
            ->assertJsonPath('data.usuario.nombre', 'José María')
            ->assertJsonPath('data.usuario.apellido', 'Cruz López');

        $this->assertDatabaseHas('users', [
            'correo' => $correo,
            'nombre' => 'José María',
            'apellido' => 'Cruz López',
        ]);
    }

    public function test_registro_rechaza_contrasena_sin_complejidad(): void
    {
        $correo = 'password.' . bin2hex(random_bytes(5)) . '@example.test';

        $this->postJson('/api/v1/auth/register', [
            'nombre' => 'María',
            'apellido' => 'López',
            'correo' => $correo,
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', ['correo' => $correo]);
    }

    public function test_login_permite_contrasena_heredada_debil_y_asigna_dispositivo_predeterminado(): void
    {
        $usuario = $this->crearUsuario('12345678');

        $this->postJson('/api/v1/auth/login', [
            'correo' => $usuario->correo,
            'password' => '12345678',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $usuario->id_usuario,
            'name' => 'TrabajITO Web',
        ]);
    }

    public function test_restauracion_de_contrasena_rechaza_contrasena_debil(): void
    {
        $usuario = $this->crearUsuario('Prueba123!');
        $tokenReset = Password::createToken($usuario);

        $this->postJson('/api/v1/auth/reset-password', [
            'correo' => $usuario->correo,
            'token' => $tokenReset,
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $usuario->refresh();
        $this->assertTrue(Hash::check('Prueba123!', $usuario->contrasena_hash));
    }

    public function test_actualizacion_rechaza_apellido_con_numeros(): void
    {
        $usuario = $this->crearUsuario();

        $this->withToken($this->token($usuario))
            ->patchJson('/api/v1/me', [
                'apellido' => 'Cruz2026',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['apellido']);

        $this->assertDatabaseHas('users', [
            'id_usuario' => $usuario->id_usuario,
            'apellido' => 'Prueba',
        ]);
    }

    public function test_cambio_de_contrasena_rechaza_contrasena_debil(): void
    {
        $usuario = $this->crearUsuario('Prueba123!');

        $this->withToken($this->token($usuario))
            ->patchJson('/api/v1/me/password', [
                'password_actual' => 'Prueba123!',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $usuario->refresh();
        $this->assertTrue(Hash::check('Prueba123!', $usuario->contrasena_hash));
    }

    public function test_creacion_de_oficio_rechaza_numeros_en_el_nombre(): void
    {
        $admin = $this->crearUsuario(administrador: true);

        $this->withToken($this->token($admin))
            ->postJson('/api/v1/admin/oficios', [
                'nombre' => 'Plomería123',
                'descripcion' => 'Instalación y reparación de tuberías.',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);

        $this->assertDatabaseMissing('oficios', ['nombre' => 'Plomería123']);
    }

    public function test_creacion_de_oficio_acepta_acentos_y_espacios(): void
    {
        $admin = $this->crearUsuario(administrador: true);

        $this->withToken($this->token($admin))
            ->postJson('/api/v1/admin/oficios', [
                'nombre' => 'Instalación eléctrica',
                'descripcion' => 'Instalación, diagnóstico y reparación de sistemas eléctricos.',
            ])->assertCreated()
            ->assertJsonPath('data.nombre', 'Instalación eléctrica');

        $this->assertDatabaseHas('oficios', ['nombre' => 'Instalación eléctrica']);
    }

    public function test_creacion_de_oficio_rechaza_duplicado_sin_distinguir_mayusculas(): void
    {
        $admin = $this->crearUsuario(administrador: true);

        Oficio::create([
            'nombre' => 'Plomería',
            'descripcion' => 'Servicio de plomería.',
            'activo' => true,
        ]);

        $this->withToken($this->token($admin))
            ->postJson('/api/v1/admin/oficios', [
                'nombre' => 'plomería',
                'descripcion' => 'Otra descripción.',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);

        $this->assertDatabaseCount('oficios', 1);
    }

    public function test_actualizacion_de_oficio_rechaza_nombre_con_numeros(): void
    {
        $admin = $this->crearUsuario(administrador: true);
        $oficio = Oficio::create([
            'nombre' => 'Carpintería',
            'descripcion' => 'Fabricación y reparación de muebles.',
            'activo' => true,
        ]);

        $this->withToken($this->token($admin))
            ->patchJson('/api/v1/admin/oficios/' . $oficio->id_oficio, [
                'nombre' => 'Carpintería2',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);

        $this->assertDatabaseHas('oficios', [
            'id_oficio' => $oficio->id_oficio,
            'nombre' => 'Carpintería',
        ]);
    }

    public function test_creacion_de_perfil_rechaza_descripcion_solo_con_espacios(): void
    {
        [, $municipio, $oficio] = $this->crearCatalogos();
        $usuario = $this->crearUsuario();

        $this->withToken($this->token($usuario))
            ->postJson('/api/v1/perfiles', [
                'id_municipio' => $municipio->id_municipio,
                'descripcion' => '          ',
                'oficios' => [$oficio->id_oficio],
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['descripcion']);

        $this->assertDatabaseCount('perfiles_profesionales', 0);
    }

    public function test_creacion_de_contratacion_rechaza_descripcion_solo_con_espacios(): void
    {
        [, $municipio] = $this->crearCatalogos();
        $trabajadorUsuario = $this->crearUsuario();
        $perfil = PerfilProfesional::create([
            'id_usuario' => $trabajadorUsuario->id_usuario,
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil profesional de prueba.',
            'activo' => true,
        ]);

        $cliente = $this->crearUsuario();

        $this->withToken($this->token($cliente))
            ->postJson('/api/v1/contrataciones', [
                'id_trabajador' => $perfil->id_trabajador,
                'descripcion_servicio' => '        ',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['descripcion_servicio']);

        $this->assertDatabaseCount('contrataciones', 0);
    }

    public function test_creacion_de_publicacion_rechaza_titulo_solo_con_espacios(): void
    {
        $usuario = $this->crearUsuario();
        $tipo = TipoPublicacion::create(['tipo_publicacion' => 'Proyecto']);

        $this->withToken($this->token($usuario))
            ->postJson('/api/v1/contrataciones/1/publicacion', [
                'id_tipo_publicacion' => $tipo->id_tipo_publicacion,
                'titulo' => '             ',
                'descripcion' => 'Descripción suficientemente extensa para la validación.',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['titulo']);

        $this->assertDatabaseCount('publicaciones', 0);
    }

    public function test_creacion_de_reporte_rechaza_descripcion_vacia(): void
    {
        $usuario = $this->crearUsuario();

        $this->withToken($this->token($usuario))
            ->postJson('/api/v1/reportes', [
                'tipo_objetivo' => 'PERFIL',
                'id_objetivo' => 1,
                'motivo' => 'Información engañosa',
                'descripcion' => '        ',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['descripcion']);

        $this->assertDatabaseCount('reportes', 0);
    }

    public function test_valoracion_rechaza_comentario_mayor_a_1000_caracteres(): void
    {
        $usuario = $this->crearUsuario();

        $this->withToken($this->token($usuario))
            ->postJson('/api/v1/publicaciones/1/valoracion', [
                'calificacion' => 5,
                'comentario' => str_repeat('a', 1001),
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['comentario']);

        $this->assertDatabaseCount('valoraciones', 0);
    }

    public function test_resolucion_de_reporte_rechaza_texto_solo_con_espacios(): void
    {
        $admin = $this->crearUsuario(administrador: true);

        $this->withToken($this->token($admin))
            ->patchJson('/api/v1/admin/reportes/1/resolver', [
                'resolucion' => '       ',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['resolucion']);
    }
}
