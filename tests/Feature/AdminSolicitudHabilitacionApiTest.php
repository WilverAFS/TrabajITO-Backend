<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\EstadoSolicitud;
use App\Models\Municipio;
use App\Models\PerfilProfesional;
use App\Models\SolicitudHabilitacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSolicitudHabilitacionApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/admin/solicitudes-habilitacion';

    private User $administrador;
    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'Pendiente',
            'En revisión',
            'Aceptada',
            'Rechazada',
        ] as $nombre) {
            EstadoSolicitud::firstOrCreate([
                'estado_solicitud' => $nombre,
            ]);
        }

        $this->administrador = $this->crearUsuario(
            'admin.api28@example.test',
            true
        );

        $this->cliente = $this->crearUsuario(
            'cliente.api28@example.test'
        );
    }

    private function crearUsuario(
        string $correo,
        bool $administrador = false,
        bool $activo = true
    ): User {
        $usuario = User::create([
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => $correo,
            'contrasena_hash' => 'Prueba123!',
        ]);

        $usuario->es_administrador = $administrador;
        $usuario->activo = $activo;
        $usuario->save();

        return $usuario;
    }

    private function crearPerfil(
        User $usuario,
        bool $activo = false
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

        $perfil = PerfilProfesional::create([
            'id_usuario' => $usuario->id_usuario,
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil profesional de prueba.',
        ]);

        $perfil->activo = $activo;
        $perfil->save();

        return $perfil;
    }

    private function crearSolicitud(
        PerfilProfesional $perfil,
        int $estado = 1
    ): SolicitudHabilitacion {
        $solicitud = $perfil
            ->solicitudesHabilitacion()
            ->create([]);

        if ($estado !== 1) {
            $solicitud->id_estado_solicitud = $estado;
            $solicitud->save();
        }

        return $solicitud;
    }

    private function token(User $usuario): string
    {
        return $usuario
            ->createToken('Pruebas API 28')
            ->plainTextToken;
    }

    private function autenticarAdministrador(): void
    {
        $this->withToken($this->token($this->administrador));
    }

    public function test_endpoints_requieren_autenticacion(): void
    {
        $url = self::URL . '/999';

        $this->getJson(self::URL)->assertUnauthorized();
        $this->getJson($url)->assertUnauthorized();
        $this->patchJson($url . '/revisar')->assertUnauthorized();
        $this->patchJson($url . '/aprobar')->assertUnauthorized();
        $this->patchJson($url . '/rechazar')->assertUnauthorized();
    }

    public function test_usuario_normal_no_puede_administrar_solicitudes(): void
    {
        $this->withToken($this->token($this->cliente));

        $url = self::URL . '/999';

        $this->getJson(self::URL)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->getJson($url)->assertForbidden();
        $this->patchJson($url . '/revisar')->assertForbidden();
        $this->patchJson($url . '/aprobar')->assertForbidden();
        $this->patchJson($url . '/rechazar')->assertForbidden();
    }

    public function test_administrador_inactivo_no_puede_acceder(): void
    {
        $inactivo = $this->crearUsuario(
            'admin.inactivo.api28@example.test',
            true,
            false
        );

        $this->withToken($this->token($inactivo))
            ->getJson(self::URL)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'ACCOUNT_INACTIVE');
    }

    public function test_administrador_lista_y_filtra_solicitudes(): void
    {
        $trabajador1 = $this->crearUsuario(
            'trabajador1.api28@example.test'
        );

        $trabajador2 = $this->crearUsuario(
            'trabajador2.api28@example.test'
        );

        $perfil1 = $this->crearPerfil($trabajador1);
        $perfil2 = $this->crearPerfil($trabajador2);

        $pendiente = $this->crearSolicitud($perfil1, 1);
        $this->crearSolicitud($perfil2, 2);

        $this->autenticarAdministrador();

        $this->getJson(
            self::URL .
            '?id_estado=1&page=1&per_page=1' .
            '&sort=fecha_solicitud&direction=asc'
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id_solicitud',
                $pendiente->id_solicitud
            )
            ->assertJsonPath(
                'data.0.estado.id_estado_solicitud',
                1
            )
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_administrador_consulta_detalle(): void
    {
        $trabajador = $this->crearUsuario(
            'detalle.api28@example.test'
        );

        $perfil = $this->crearPerfil($trabajador);
        $solicitud = $this->crearSolicitud($perfil);

        $certificado = $perfil->certificados()->create([
            'nombre' => 'Certificado de electricidad',
            'institucion_emisora' => 'Instituto de capacitación',
            'fecha_emision' => '2025-05-15',
            'fecha_vencimiento' => '2027-05-15',
            'ruta_documento' => 'certificados/solicitud-prueba.pdf',
        ]);
        $certificado->forceFill(['verificado' => true])->save();

        $this->autenticarAdministrador();

        $this->getJson(
            self::URL . '/' . $solicitud->id_solicitud
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.id_solicitud',
                $solicitud->id_solicitud
            )
            ->assertJsonPath(
                'data.perfil.id_trabajador',
                $perfil->id_trabajador
            )
            ->assertJsonPath(
                'data.perfil.nombre_completo',
                'Usuario Prueba'
            )
            ->assertJsonPath(
                'data.estado.estado_solicitud',
                'Pendiente'
            )
            ->assertJsonCount(1, 'data.perfil.certificados')
            ->assertJsonPath(
                'data.perfil.certificados.0.id_certificado',
                $certificado->id_certificado
            )
            ->assertJsonPath(
                'data.perfil.certificados.0.nombre',
                'Certificado de electricidad'
            )
            ->assertJsonPath(
                'data.perfil.certificados.0.institucion_emisora',
                'Instituto de capacitación'
            )
            ->assertJsonPath(
                'data.perfil.certificados.0.fecha_emision',
                '2025-05-15'
            )
            ->assertJsonPath(
                'data.perfil.certificados.0.fecha_vencimiento',
                '2027-05-15'
            )
            ->assertJsonPath(
                'data.perfil.certificados.0.verificado',
                true
            )
            ->assertJsonPath(
                'data.perfil.certificados.0.documento_url',
                route('api.v1.certificados.documento', [
                    'id_certificado' => $certificado->id_certificado,
                ])
            )
            ->assertJsonMissingPath(
                'data.perfil.certificados.0.ruta_documento'
            )
            ->assertJsonStructure([
                'data' => [
                    'id_solicitud',
                    'perfil' => [
                        'id_trabajador',
                        'nombre_completo',
                        'descripcion',
                        'municipio',
                        'oficios',
                        'certificados',
                    ],
                    'estado',
                    'administrador_revisor',
                    'comentario_revision',
                    'fecha_solicitud',
                    'fecha_revision',
                ],
            ]);
    }

    public function test_solicitudes_inexistentes_devuelven_404(): void
    {
        $this->autenticarAdministrador();

        $url = self::URL . '/999999';

        $this->getJson($url)
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'RESOURCE_NOT_FOUND'
            );

        $this->patchJson($url . '/revisar')
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'RESOURCE_NOT_FOUND'
            );

        $this->patchJson($url . '/aprobar')
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'RESOURCE_NOT_FOUND'
            );

        $this->patchJson($url . '/rechazar', [
            'comentario_revision' => 'Solicitud inexistente.',
        ])
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'RESOURCE_NOT_FOUND'
            );
    }

    public function test_administrador_marca_solicitud_en_revision(): void
    {
        $perfil = $this->crearPerfil($this->cliente);
        $solicitud = $this->crearSolicitud($perfil);

        $this->autenticarAdministrador();

        $this->patchJson(
            self::URL . '/' . $solicitud->id_solicitud . '/revisar'
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.estado.id_estado_solicitud',
                2
            )
            ->assertJsonPath(
                'data.administrador_revisor.id_usuario',
                $this->administrador->id_usuario
            );

        $this->assertDatabaseHas('solicitudes_habilitacion', [
            'id_solicitud' => $solicitud->id_solicitud,
            'id_estado_solicitud' => 2,
            'id_admin_revisor' => $this->administrador->id_usuario,
        ]);

        $this->assertNotNull(
            $solicitud->fresh()->fecha_revision
        );
    }

    public function test_no_permite_revisar_solicitud_ya_en_revision(): void
    {
        $perfil = $this->crearPerfil($this->cliente);
        $solicitud = $this->crearSolicitud($perfil, 2);

        $this->autenticarAdministrador();

        $this->patchJson(
            self::URL . '/' . $solicitud->id_solicitud . '/revisar'
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'error_code',
                'OPERATION_FAILED'
            );
    }

    public function test_aprobar_solicitud_activa_perfil(): void
    {
        $perfil = $this->crearPerfil($this->cliente);
        $solicitud = $this->crearSolicitud($perfil);

        $this->autenticarAdministrador();

        $this->patchJson(
            self::URL . '/' . $solicitud->id_solicitud . '/aprobar',
            [
                'comentario_revision' =>
                    'La documentación fue validada.',
            ]
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.estado.id_estado_solicitud',
                3
            )
            ->assertJsonPath(
                'data.administrador_revisor.id_usuario',
                $this->administrador->id_usuario
            )
            ->assertJsonPath(
                'data.comentario_revision',
                'La documentación fue validada.'
            )
            ->assertJsonPath('data.perfil_activo', true);

        $this->assertDatabaseHas('solicitudes_habilitacion', [
            'id_solicitud' => $solicitud->id_solicitud,
            'id_estado_solicitud' => 3,
            'id_admin_revisor' => $this->administrador->id_usuario,
        ]);

        $this->assertDatabaseHas('perfiles_profesionales', [
            'id_trabajador' => $perfil->id_trabajador,
            'activo' => true,
        ]);

        $this->assertNotNull(
            $solicitud->fresh()->fecha_revision
        );
    }

    public function test_aprobar_solicitud_que_esta_en_revision(): void
    {
        $perfil = $this->crearPerfil($this->cliente);
        $solicitud = $this->crearSolicitud($perfil, 2);

        $this->autenticarAdministrador();

        $this->patchJson(
            self::URL . '/' . $solicitud->id_solicitud . '/aprobar',
            [
                'comentario_revision' => 'Perfil verificado.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.estado.id_estado_solicitud',
                3
            )
            ->assertJsonPath('data.perfil_activo', true);
    }

    public function test_rechazar_solicitud_exige_comentario(): void
    {
        $perfil = $this->crearPerfil($this->cliente);
        $solicitud = $this->crearSolicitud($perfil);

        $this->autenticarAdministrador();

        $this->patchJson(
            self::URL . '/' . $solicitud->id_solicitud . '/rechazar',
            []
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'comentario_revision',
            ]);

        $this->assertDatabaseHas('solicitudes_habilitacion', [
            'id_solicitud' => $solicitud->id_solicitud,
            'id_estado_solicitud' => 1,
        ]);
    }

    public function test_administrador_rechaza_solicitud(): void
    {
        $perfil = $this->crearPerfil($this->cliente);
        $solicitud = $this->crearSolicitud($perfil);

        $this->autenticarAdministrador();

        $this->patchJson(
            self::URL . '/' . $solicitud->id_solicitud . '/rechazar',
            [
                'comentario_revision' =>
                    'Falta documentación profesional.',
            ]
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.estado.id_estado_solicitud',
                4
            )
            ->assertJsonPath(
                'data.comentario_revision',
                'Falta documentación profesional.'
            );

        $this->assertDatabaseHas('solicitudes_habilitacion', [
            'id_solicitud' => $solicitud->id_solicitud,
            'id_estado_solicitud' => 4,
            'id_admin_revisor' => $this->administrador->id_usuario,
        ]);

        $this->assertDatabaseHas('perfiles_profesionales', [
            'id_trabajador' => $perfil->id_trabajador,
            'activo' => false,
        ]);
    }

    public function test_no_permite_modificar_solicitud_resuelta(): void
    {
        $perfil = $this->crearPerfil($this->cliente);
        $solicitud = $this->crearSolicitud($perfil, 4);

        $this->autenticarAdministrador();

        $url = self::URL . '/' . $solicitud->id_solicitud;

        $this->patchJson($url . '/revisar')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'OPERATION_FAILED');

        $this->patchJson($url . '/aprobar')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'OPERATION_FAILED');

        $this->patchJson($url . '/rechazar', [
            'comentario_revision' => 'Otra revisión.',
        ])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'OPERATION_FAILED');

        $this->assertDatabaseHas('solicitudes_habilitacion', [
            'id_solicitud' => $solicitud->id_solicitud,
            'id_estado_solicitud' => 4,
        ]);
    }

    public function test_rechaza_filtros_invalidos(): void
    {
        $this->autenticarAdministrador();

        $this->getJson(self::URL . '?id_estado=32768')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_estado']);

        $this->getJson(self::URL . '?id_estado=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_estado']);

        $this->getJson(self::URL . '?sort=nombre')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);

        $this->getJson(self::URL . '?direction=invalida')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['direction']);
    }

    public function test_estado_valido_sin_solicitudes_devuelve_lista_vacia(): void
    {
        $this->autenticarAdministrador();

        $this->getJson(self::URL . '?id_estado=4')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
