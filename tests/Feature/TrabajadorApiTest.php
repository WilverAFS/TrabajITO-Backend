<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Oficio;
use App\Models\PerfilProfesional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrabajadorApiTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(
        string $nombre = 'Usuario',
        bool $activo = true
    ): User {
        $usuario = User::create([
            'nombre' => $nombre,
            'apellido' => 'Prueba',
            'correo' => 'trabajador.' .
                bin2hex(random_bytes(6)) . '@example.test',
            'telefono' => null,
            'contrasena_hash' => 'Prueba123!',
        ]);

        $usuario->activo = $activo;
        $usuario->save();

        return $usuario;
    }

    private function crearMunicipio(
        string $nombre = 'Municipio de prueba'
    ): Municipio {
        $sufijo = bin2hex(random_bytes(4));

        $estado = Estado::create([
            'estado' => 'Estado ' . $sufijo,
            'activo' => true,
        ]);

        return Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => $nombre . ' ' . $sufijo,
            'activo' => true,
        ]);
    }

    private function crearOficio(
        string $nombre = 'Electricidad'
    ): Oficio {
        return Oficio::create([
            'nombre' => $nombre . ' ' .
                bin2hex(random_bytes(4)),
            'descripcion' => 'Descripción de oficio de prueba',
            'activo' => true,
        ]);
    }

    private function crearPerfil(
        User $usuario,
        Municipio $municipio,
        array $oficios = [],
        bool $activo = true,
        string $descripcion = 'Profesional de prueba'
    ): PerfilProfesional {
        $perfil = $usuario->perfilProfesional()->create([
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => $descripcion,
        ]);

        // Habilitar el perfil solo para las pruebas.
        $perfil->activo = $activo;
        $perfil->save();

        if ($oficios !== []) {
            $perfil->oficios()->sync(
                array_map(
                    fn (Oficio $oficio) => $oficio->id_oficio,
                    $oficios
                )
            );
        }

        return $perfil;
    }

    // 1. Listar trabajadores habilitados.
    public function test_listado_publico_devuelve_trabajadores_habilitados(): void
    {
        $municipio = $this->crearMunicipio();
        $oficio = $this->crearOficio();

        $perfil = $this->crearPerfil(
            $this->crearUsuario('Carlos'),
            $municipio,
            [$oficio],
            true,
            'Electricista con experiencia residencial'
        );

        $this->getJson('/api/v1/trabajadores')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.0.id_trabajador',
                $perfil->id_trabajador
            )
            ->assertJsonPath(
                'data.0.nombre_completo',
                'Carlos Prueba'
            )
            ->assertJsonPath(
                'data.0.total_valoraciones',
                0
            )
            ->assertJsonPath(
                'data.0.calificacion_promedio',
                null
            )
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.correo');
    }

    // 2. Excluir perfiles y usuarios inactivos.
    public function test_excluye_perfiles_inactivos_y_usuarios_inactivos(): void
    {
        $municipio = $this->crearMunicipio();

        $visible = $this->crearPerfil(
            $this->crearUsuario('Visible'),
            $municipio
        );

        $this->crearPerfil(
            $this->crearUsuario('SinHabilitar'),
            $municipio,
            [],
            false
        );

        $this->crearPerfil(
            $this->crearUsuario('Suspendido', false),
            $municipio
        );

        $this->getJson('/api/v1/trabajadores')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.id_trabajador',
                $visible->id_trabajador
            )
            ->assertJsonCount(1, 'data');
    }

    // 3. Filtrar trabajadores por oficio.
    public function test_filtra_trabajadores_por_oficio(): void
    {
        $municipio = $this->crearMunicipio();

        $plomeria = $this->crearOficio('Plomería');
        $electricidad = $this->crearOficio('Electricidad');

        $esperado = $this->crearPerfil(
            $this->crearUsuario('Elena'),
            $municipio,
            [$plomeria]
        );

        $this->crearPerfil(
            $this->crearUsuario('Carlos'),
            $municipio,
            [$electricidad]
        );

        $this->getJson(
            '/api/v1/trabajadores?id_oficio=' .
            $plomeria->id_oficio
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id_trabajador',
                $esperado->id_trabajador
            );
    }

    // 4. Filtrar trabajadores por municipio.
    public function test_filtra_trabajadores_por_municipio(): void
    {
        $oaxaca = $this->crearMunicipio(
            'Oaxaca de Juárez'
        );

        $otro = $this->crearMunicipio(
            'Otro municipio'
        );

        $esperado = $this->crearPerfil(
            $this->crearUsuario('Carlos'),
            $oaxaca
        );

        $this->crearPerfil(
            $this->crearUsuario('Elena'),
            $otro
        );

        $this->getJson(
            '/api/v1/trabajadores?id_municipio=' .
            $oaxaca->id_municipio
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id_trabajador',
                $esperado->id_trabajador
            );
    }

    // 5. Buscar trabajadores por texto.
    public function test_permite_buscar_trabajadores_por_texto(): void
    {
        $municipio = $this->crearMunicipio();

        $esperado = $this->crearPerfil(
            $this->crearUsuario('Carlos'),
            $municipio,
            [],
            true,
            'Electricista residencial con experiencia'
        );

        $this->crearPerfil(
            $this->crearUsuario('Elena'),
            $municipio,
            [],
            true,
            'Especialista en instalaciones sanitarias'
        );

        $this->getJson(
            '/api/v1/trabajadores?buscar=electricista'
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id_trabajador',
                $esperado->id_trabajador
            );
    }

    // 6. Filtrar por disponibilidad activa.
    public function test_filtra_por_disponibilidad_activa_del_dia(): void
    {
        $municipio = $this->crearMunicipio();

        $disponible = $this->crearPerfil(
            $this->crearUsuario('Carlos'),
            $municipio
        );

        $otro = $this->crearPerfil(
            $this->crearUsuario('Elena'),
            $municipio
        );

        $disponible->disponibilidades()->create([
            'dia_semana' => 1,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '14:00:00',
            'activo' => true,
        ]);

        $otro->disponibilidades()->create([
            'dia_semana' => 2,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '14:00:00',
            'activo' => true,
        ]);

        $this->getJson(
            '/api/v1/trabajadores?dia_semana=1'
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id_trabajador',
                $disponible->id_trabajador
            );
    }

    // 7. Excluir perfiles sin valoraciones.
    public function test_calificacion_minima_excluye_perfiles_sin_valoraciones(): void
    {
        $municipio = $this->crearMunicipio();

        $this->crearPerfil(
            $this->crearUsuario('SinValoraciones'),
            $municipio
        );

        $this->getJson(
            '/api/v1/trabajadores?calificacion_minima=4'
        )
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    // 8. Comprobar paginación.
    public function test_paginacion_respeta_per_page_y_page(): void
    {
        $municipio = $this->crearMunicipio();

        for ($i = 1; $i <= 3; $i++) {
            $this->crearPerfil(
                $this->crearUsuario('Trabajador' . $i),
                $municipio
            );
        }

        $this->getJson(
            '/api/v1/trabajadores?per_page=1&page=2'
        )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 3);
    }

    // 9. Comprobar privacidad del perfil público.
    public function test_detalle_publico_no_expone_informacion_privada(): void
    {
        $municipio = $this->crearMunicipio();
        $oficio = $this->crearOficio();

        $usuario = $this->crearUsuario('Carlos');

        $perfil = $this->crearPerfil(
            $usuario,
            $municipio,
            [$oficio]
        );

        $this->getJson(
            '/api/v1/trabajadores/' .
            $perfil->id_trabajador
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.id_trabajador',
                $perfil->id_trabajador
            )
            ->assertJsonPath(
                'data.usuario.nombre',
                'Carlos'
            )
            ->assertJsonPath(
                'data.total_valoraciones',
                0
            )
            ->assertJsonMissingPath('data.usuario.correo')
            ->assertJsonMissingPath(
                'data.usuario.contrasena_hash'
            )
            ->assertJsonMissingPath(
                'data.solicitudes_habilitacion'
            )
            ->assertJsonMissingPath(
                'data.contrataciones'
            );
    }

    // 10. Comprobar error 404.
    public function test_detalle_devuelve_404_para_inexistente_o_inactivo(): void
    {
        $municipio = $this->crearMunicipio();

        $inactivo = $this->crearPerfil(
            $this->crearUsuario('Inactivo'),
            $municipio,
            [],
            false
        );

        $this->getJson(
            '/api/v1/trabajadores/999999'
        )
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'WORKER_NOT_FOUND'
            );

        $this->getJson(
            '/api/v1/trabajadores/' .
            $inactivo->id_trabajador
        )
            ->assertNotFound()
            ->assertJsonPath(
                'error_code',
                'WORKER_NOT_FOUND'
            );
    }

    // 11. Comprobar filtros inválidos.
    public function test_filtros_invalidos_devuelven_error_422(): void
    {
        $this->getJson(
            '/api/v1/trabajadores?' .
            'per_page=51&dia_semana=9&sort=incorrecto'
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'per_page',
                'dia_semana',
                'sort',
            ]);
    }
}
