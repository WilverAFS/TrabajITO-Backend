<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoPerfilApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function crearUsuario(bool $administrador = false): User
    {
        $usuario = User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => uniqid('foto-', true) . '@example.test',
            'telefono' => null,
            'contrasena_hash' => 'Prueba123!',
        ]);

        if ($administrador) {
            $usuario->es_administrador = true;
            $usuario->save();
        }

        return $usuario->fresh();
    }

    private function crearPerfil(
        User $usuario,
        bool $activo = true
    ): PerfilProfesional {
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

        $perfil = $usuario->perfilProfesional()->create([
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil profesional para pruebas de fotografía.',
        ]);

        $perfil->activo = $activo;
        $perfil->save();

        return $perfil;
    }

    private function crearToken(User $usuario): string
    {
        return $usuario->createToken('Pruebas fotografía de cuenta')
            ->plainTextToken;
    }

    private function guardarFoto(
        User $usuario,
        string $nombre = 'foto.jpg'
    ): string {
        $ruta = UploadedFile::fake()
            ->image($nombre, 20, 20)
            ->store('fotos-perfil', 'local');

        $usuario->foto_perfil = $ruta;
        $usuario->save();

        return $ruta;
    }

    public function test_cliente_trabajador_y_administrador_pueden_subir_foto_de_cuenta(): void
    {
        $cliente = $this->crearUsuario();
        $trabajador = $this->crearUsuario();
        $this->crearPerfil($trabajador);
        $administrador = $this->crearUsuario(true);

        foreach ([
            $cliente,
            $trabajador,
            $administrador,
        ] as $usuario) {
            // Cada iteración representa una petición autenticada independiente.
            // Laravel puede conservar la instancia del guard entre peticiones
            // dentro del mismo test; se limpia para respetar el nuevo Bearer token.
            Auth::forgetGuards();

            $respuesta = $this->withToken($this->crearToken($usuario))
                ->post('/api/v1/me/foto-perfil', [
                    'foto' => UploadedFile::fake()->image('cuenta.jpg', 20, 20),
                ]);

            $respuesta->assertCreated()
                ->assertJsonPath('success', true)
                ->assertJsonPath(
                    'data.foto_url',
                    route('api.v1.usuarios.foto', [
                        'id_usuario' => $usuario->id_usuario,
                    ])
                );

            $usuario->refresh();

            $this->assertNotEmpty($usuario->foto_perfil);
            Storage::disk('local')->assertExists($usuario->foto_perfil);

            $this->assertDatabaseHas('users', [
                'id_usuario' => $usuario->id_usuario,
                'foto_perfil' => $usuario->foto_perfil,
            ]);
        }
    }

    public function test_subir_fotografia_requiere_autenticacion(): void
    {
        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/me/foto-perfil', [
                'foto' => UploadedFile::fake()->image('sin-sesion.jpg'),
            ])->assertUnauthorized();
    }

    public function test_reemplazar_fotografia_elimina_el_archivo_anterior(): void
    {
        $usuario = $this->crearUsuario();
        $token = $this->crearToken($usuario);

        $this->withToken($token)
            ->post('/api/v1/me/foto-perfil', [
                'foto' => UploadedFile::fake()->image('primera.jpg'),
            ])->assertCreated();

        $usuario->refresh();
        $rutaAnterior = $usuario->foto_perfil;
        Storage::disk('local')->assertExists($rutaAnterior);

        $this->withToken($token)
            ->post('/api/v1/me/foto-perfil', [
                'foto' => UploadedFile::fake()->image('segunda.jpg'),
            ])->assertCreated();

        $usuario->refresh();

        $this->assertNotSame($rutaAnterior, $usuario->foto_perfil);
        Storage::disk('local')->assertMissing($rutaAnterior);
        Storage::disk('local')->assertExists($usuario->foto_perfil);
    }

    public function test_eliminar_fotografia_limpia_la_ruta_y_el_archivo(): void
    {
        $usuario = $this->crearUsuario();
        $ruta = $this->guardarFoto($usuario);
        $token = $this->crearToken($usuario);

        $this->withToken($token)
            ->deleteJson('/api/v1/me/foto-perfil')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.foto_url', null);

        $usuario->refresh();

        $this->assertNull($usuario->foto_perfil);
        Storage::disk('local')->assertMissing($ruta);

        $this->assertDatabaseHas('users', [
            'id_usuario' => $usuario->id_usuario,
            'foto_perfil' => null,
        ]);
    }

    public function test_rechaza_archivos_que_no_son_imagenes_permitidas(): void
    {
        $usuario = $this->crearUsuario();

        $this->withToken($this->crearToken($usuario))
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/me/foto-perfil', [
                'foto' => UploadedFile::fake()->create(
                    'documento.pdf',
                    100,
                    'application/pdf'
                ),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto']);
    }

    public function test_rechaza_fotografias_mayores_de_cinco_megabytes(): void
    {
        $usuario = $this->crearUsuario();

        $this->withToken($this->crearToken($usuario))
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/me/foto-perfil', [
                'foto' => UploadedFile::fake()
                    ->image('foto-grande.jpg')
                    ->size(5121),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto']);
    }

    public function test_titular_puede_consultar_su_fotografia_privada(): void
    {
        $usuario = $this->crearUsuario();
        $this->guardarFoto($usuario);

        $this->withToken($this->crearToken($usuario))
            ->get(route('api.v1.usuarios.foto', [
                'id_usuario' => $usuario->id_usuario,
            ]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_administrador_puede_consultar_fotografia_privada_de_otro_usuario(): void
    {
        $cliente = $this->crearUsuario();
        $this->guardarFoto($cliente);

        $administrador = $this->crearUsuario(true);

        $this->withToken($this->crearToken($administrador))
            ->get(route('api.v1.usuarios.foto', [
                'id_usuario' => $cliente->id_usuario,
            ]))
            ->assertOk();
    }

    public function test_usuario_ajeno_no_puede_consultar_fotografia_privada(): void
    {
        $cliente = $this->crearUsuario();
        $this->guardarFoto($cliente);

        $otroUsuario = $this->crearUsuario();

        $this->withToken($this->crearToken($otroUsuario))
            ->getJson(route('api.v1.usuarios.foto', [
                'id_usuario' => $cliente->id_usuario,
            ]))
            ->assertForbidden()
            ->assertJsonPath('error_code', 'PHOTO_ACCESS_DENIED');
    }

    public function test_fotografia_de_trabajador_activo_es_publica(): void
    {
        $trabajador = $this->crearUsuario();
        $this->crearPerfil($trabajador, true);
        $this->guardarFoto($trabajador);

        $this->get(route('api.v1.usuarios.foto', [
            'id_usuario' => $trabajador->id_usuario,
        ]))->assertOk();
    }

    public function test_fotografia_de_perfil_profesional_inactivo_no_es_publica(): void
    {
        $trabajador = $this->crearUsuario();
        $this->crearPerfil($trabajador, false);
        $this->guardarFoto($trabajador);

        $this->getJson(route('api.v1.usuarios.foto', [
            'id_usuario' => $trabajador->id_usuario,
        ]))
            ->assertNotFound()
            ->assertJsonPath('error_code', 'PHOTO_ACCESS_DENIED');
    }

    public function test_fotografia_privada_no_es_visible_sin_autenticacion(): void
    {
        $cliente = $this->crearUsuario();
        $this->guardarFoto($cliente);

        $this->getJson(route('api.v1.usuarios.foto', [
            'id_usuario' => $cliente->id_usuario,
        ]))
            ->assertNotFound()
            ->assertJsonPath('error_code', 'PHOTO_ACCESS_DENIED');
    }

    public function test_usuario_sin_fotografia_recibe_not_found(): void
    {
        $usuario = $this->crearUsuario();

        $this->withToken($this->crearToken($usuario))
            ->getJson(route('api.v1.usuarios.foto', [
                'id_usuario' => $usuario->id_usuario,
            ]))
            ->assertNotFound()
            ->assertJsonPath('error_code', 'PHOTO_NOT_FOUND');
    }

    public function test_respuestas_de_cuenta_y_login_incluyen_foto_url(): void
    {
        $usuario = $this->crearUsuario();
        $this->guardarFoto($usuario);

        $fotoUrl = route('api.v1.usuarios.foto', [
            'id_usuario' => $usuario->id_usuario,
        ]);

        $this->withToken($this->crearToken($usuario))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.foto_url', $fotoUrl);

        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/auth/login', [
            'correo' => $usuario->correo,
            'password' => 'Prueba123!',
            'device_name' => 'Prueba fotografía',
        ])
            ->assertOk()
            ->assertJsonPath('data.usuario.foto_url', $fotoUrl);
    }

    public function test_recursos_publicos_de_trabajador_incluyen_foto_url(): void
    {
        $trabajador = $this->crearUsuario();
        $perfil = $this->crearPerfil($trabajador, true);
        $this->guardarFoto($trabajador);

        $fotoUrl = route('api.v1.usuarios.foto', [
            'id_usuario' => $trabajador->id_usuario,
        ]);

        $this->getJson('/api/v1/trabajadores')
            ->assertOk()
            ->assertJsonPath('data.0.foto_url', $fotoUrl);

        $this->getJson("/api/v1/trabajadores/{$perfil->id_trabajador}")
            ->assertOk()
            ->assertJsonPath('data.usuario.foto_url', $fotoUrl);
    }
}
