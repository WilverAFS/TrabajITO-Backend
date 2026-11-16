<?php

namespace Tests\Feature;

use App\Models\Oficio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOficioApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = '/api/v1/admin/oficios';

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function crearUsuario(
        bool $administrador = false,
        bool $activo = true
    ): User {
        $usuario = User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => uniqid().'@test.com',
            'contrasena_hash' => 'Password123!',
        ]);

        $usuario->es_administrador = $administrador;
        $usuario->activo = $activo;
        $usuario->save();

        return $usuario->fresh();
    }

    private function token(
        User $usuario
    ): string {
        return $usuario
            ->createToken('Testing')
            ->plainTextToken;
    }

    private function crearOficio(
        string $nombre = 'Electricidad',
        bool $activo = true
    ): Oficio {
        return Oficio::create([
            'nombre' => $nombre,
            'descripcion' => 'Descripción de prueba.',
            'activo' => $activo,
        ]);
    }

    public function test_requiere_autenticacion(): void
    {
        $response = $this->getJson(
            self::BASE_URL
        );

        $response->assertUnauthorized();
    }

    public function test_requiere_administrador(): void
    {
        $usuario = $this->crearUsuario(
            administrador: false
        );

        $response = $this
            ->withToken(
                $this->token($usuario)
            )
            ->getJson(
                self::BASE_URL
            );

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'No tienes permisos para acceder a este recurso.',
                'error_code' => 'FORBIDDEN',
                'errors' => null,
            ]);
    }

    public function test_listar_oficios(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $this->crearOficio(
            nombre: 'Electricidad'
        );

        $this->crearOficio(
            nombre: 'Plomería'
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->getJson(
                self::BASE_URL
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Oficios obtenidos correctamente.',
            ]);

        $this->assertCount(
            2,
            $response->json('data')
        );
    }

    public function test_incluir_inactivos(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $activo = $this->crearOficio(
            nombre: 'Electricidad',
            activo: true
        );

        $inactivo = $this->crearOficio(
            nombre: 'Plomería',
            activo: false
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->getJson(
                self::BASE_URL
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data'
            );

        $ids = collect(
            $response->json('data')
        )->pluck('id_oficio');

        $this->assertTrue(
            $ids->contains(
                $activo->id_oficio
            )
        );

        $this->assertTrue(
            $ids->contains(
                $inactivo->id_oficio
            )
        );
    }

    public function test_crear_oficio(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->postJson(
                self::BASE_URL,
                [
                    'nombre' => 'Cerrajería',
                    'descripcion' => 'Apertura, instalación y reparación de cerraduras.',
                ]
            );

        $response
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Oficio creado correctamente.',
                'data' => [
                    'nombre' => 'Cerrajería',
                    'descripcion' => 'Apertura, instalación y reparación de cerraduras.',
                    'activo' => true,
                ],
            ]);

        $this->assertDatabaseHas(
            'oficios',
            [
                'nombre' => 'Cerrajería',
                'activo' => true,
            ]
        );
    }

    public function test_validar_nombre_requerido(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->postJson(
                self::BASE_URL,
                [
                    'descripcion' => 'Descripción de prueba.',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombre',
            ]);
    }

    public function test_actualizar_oficio(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $oficio = $this->crearOficio(
            nombre: 'Cerrajería'
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                self::BASE_URL.
                '/'.
                $oficio->id_oficio,
                [
                    'nombre' => 'Cerrajería residencial',
                    'descripcion' => 'Instalación y reparación de cerraduras residenciales.',
                ]
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Oficio actualizado correctamente.',
                'data' => [
                    'id_oficio' => $oficio->id_oficio,
                    'nombre' => 'Cerrajería residencial',
                    'descripcion' => 'Instalación y reparación de cerraduras residenciales.',
                    'activo' => true,
                ],
            ]);

        $this->assertDatabaseHas(
            'oficios',
            [
                'id_oficio' => $oficio->id_oficio,
                'nombre' => 'Cerrajería residencial',
            ]
        );
    }

    public function test_oficio_inexistente(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                self::BASE_URL.
                '/999999',
                [
                    'nombre' => 'Nombre actualizado',
                ]
            );

        $response
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Oficio no encontrado.',
                'error_code' => 'RESOURCE_NOT_FOUND',
            ]);
    }

    public function test_desactivar_oficio(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $oficio = $this->crearOficio(
            nombre: 'Electricidad',
            activo: true
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                self::BASE_URL.
                '/'.
                $oficio->id_oficio.
                '/desactivar'
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Oficio desactivado correctamente.',
                'data' => [
                    'id_oficio' => $oficio->id_oficio,
                    'activo' => false,
                ],
            ]);

        $oficio->refresh();

        $this->assertFalse(
            $oficio->activo
        );
    }

    public function test_no_desactivar_dos_veces(): void
    {
        $admin = $this->crearUsuario(
            administrador: true
        );

        $oficio = $this->crearOficio(
            nombre: 'Electricidad',
            activo: false
        );

        $response = $this
            ->withToken(
                $this->token($admin)
            )
            ->patchJson(
                self::BASE_URL.
                '/'.
                $oficio->id_oficio.
                '/desactivar'
            );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Oficio desactivado correctamente.',
                'data' => [
                    'id_oficio' => $oficio->id_oficio,
                    'activo' => false,
                ],
            ]);
    }
}
