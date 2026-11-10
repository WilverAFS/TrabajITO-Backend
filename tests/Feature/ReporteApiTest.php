<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\EstadoReporte;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Contratacion;
use App\Models\EstadoContratacion;
use App\Models\Publicacion;
use App\Models\TipoPublicacion;

class ReporteApiTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private PerfilProfesional $perfil;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear el estado inicial de los reportes.
        EstadoReporte::create([
            'estado_reporte' => 'Pendiente',
        ]);

        // Crear un usuario de prueba.
        $this->usuario = User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => uniqid() . '@test.com',
            'contrasena_hash' => 'Password123!',
        ]);

        // Crear los catálogos necesarios.
        $estado = Estado::create([
            'estado' => 'Estado de prueba',
        ]);

        $municipio = Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Municipio de prueba',
        ]);

        // Crear un perfil que pueda ser reportado.
        $this->perfil = PerfilProfesional::create([
            'id_usuario' => $this->usuario->id_usuario,
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil profesional de prueba.',
        ]);
    }

    private function token(): string
    {
        return $this->usuario
            ->createToken('Testing API 25')
            ->plainTextToken;
    }

    private function datosReporte(): array
    {
        return [
            'tipo_objetivo' => 'PERFIL',
            'id_objetivo' => $this->perfil->id_trabajador,
            'motivo' => 'Información engañosa',
            'descripcion' => 'El perfil contiene información incorrecta.',
        ];
    }

    public function test_usuario_autenticado_puede_reportar_un_perfil(): void
    {
        $respuesta = $this->withToken($this->token())
            ->postJson('/api/v1/reportes', $this->datosReporte());

        $respuesta->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.tipo_objetivo',
                'PERFIL'
            )
            ->assertJsonPath(
                'data.estado.estado_reporte',
                'Pendiente'
            );

        $this->assertDatabaseHas('reportes', [
            'id_denunciante' => $this->usuario->id_usuario,
            'tipo_objetivo' => 'PERFIL',
            'id_objetivo' => $this->perfil->id_trabajador,
            'id_estado_reporte' => 1,
        ]);
    }

    public function test_usuario_no_autenticado_no_puede_reportar(): void
    {
        $this->postJson(
            '/api/v1/reportes',
            $this->datosReporte()
        )->assertStatus(401);
    }

    public function test_no_permite_reportar_un_perfil_inexistente(): void
    {
        $datos = $this->datosReporte();
        $datos['id_objetivo'] = 999999;

        $this->withToken($this->token())
            ->postJson('/api/v1/reportes', $datos)
            ->assertStatus(404)
            ->assertJsonPath(
                'error_code',
                'REPORT_TARGET_NOT_FOUND'
            );

        $this->assertDatabaseCount('reportes', 0);
    }

    public function test_rechaza_datos_invalidos(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v1/reportes', [
                'tipo_objetivo' => 'INVALIDO',
                'id_objetivo' => 0,
                'motivo' => '',
                'descripcion' => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'tipo_objetivo',
                'id_objetivo',
                'motivo',
                'descripcion',
            ]);

        $this->assertDatabaseCount('reportes', 0);
    }


public function test_usuario_autenticado_puede_reportar_una_publicacion(): void
{
    // Preparar los estados de contratación.
    foreach ([
        'Pendiente',
        'Aceptada',
        'Rechazada',
        'En proceso',
        'Finalizada',
        'Cancelada',
    ] as $nombre) {
        EstadoContratacion::create([
            'estado_contratacion' => $nombre,
        ]);
    }

    // Crear tipo de publicación.
    $tipo = TipoPublicacion::create([
        'tipo_publicacion' => 'Proyecto',
    ]);

    // Crear cliente para una contratación de prueba.
    $cliente = User::create([
        'nombre' => 'Cliente',
        'apellido' => 'Prueba',
        'correo' => 'cliente.reporte@test.com',
        'contrasena_hash' => 'Password123!',
    ]);

    // Crear una contratación finalizada.
    $contratacion = Contratacion::create([
        'id_cliente' => $cliente->id_usuario,
        'id_trabajador' => $this->perfil->id_trabajador,
        'id_estado_contratacion' => 5,
        'descripcion_servicio' => 'Servicio de prueba finalizado.',
        'fecha_solicitud' => now()->subDays(2),
        'fecha_respuesta' => now()->subDay(),
        'fecha_finalizacion' => now(),
    ]);

    // Crear publicación asociada a la contratación.
    $publicacion = Publicacion::create([
        'id_contratacion' => $contratacion->id_contratacion,
        'id_tipo_publicacion' => $tipo->id_tipo_publicacion,
        'titulo' => 'Publicación de prueba',
        'descripcion' => 'Publicación utilizada para probar reportes.',
        'visible' => true,
        'fecha_publicacion' => now(),
    ]);

    // Preparar los datos del reporte.
    $datos = $this->datosReporte();
    $datos['tipo_objetivo'] = 'PUBLICACION';
    $datos['id_objetivo'] = $publicacion->id_publicacion;

    // Enviar la solicitud autenticada.
    $this->withToken($this->token())
        ->postJson('/api/v1/reportes', $datos)
        ->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.tipo_objetivo', 'PUBLICACION')
        ->assertJsonPath(
            'data.id_objetivo',
            $publicacion->id_publicacion
        )
        ->assertJsonPath(
            'data.estado.estado_reporte',
            'Pendiente'
        );

    // Comprobar que el reporte quedó guardado.
    $this->assertDatabaseHas('reportes', [
        'id_denunciante' => $this->usuario->id_usuario,
        'tipo_objetivo' => 'PUBLICACION',
        'id_objetivo' => $publicacion->id_publicacion,
        'id_estado_reporte' => 1,
    ]);
}


public function test_no_permite_reportar_una_publicacion_inexistente(): void
{
    // Preparar los datos de una publicación inexistente.
    $datos = $this->datosReporte();

    $datos['tipo_objetivo'] = 'PUBLICACION';
    $datos['id_objetivo'] = 999999;

    // Enviar la solicitud autenticada.
    $this->withToken($this->token())
        ->postJson('/api/v1/reportes', $datos)
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath(
            'error_code',
            'REPORT_TARGET_NOT_FOUND'
        );

    // Verificar que no se haya creado ningún reporte.
    $this->assertDatabaseCount('reportes', 0);
}
}
