<?php

namespace Tests\Feature;

use App\Models\Contratacion;
use App\Models\Estado;
use App\Models\EstadoContratacion;
use App\Models\EstadoReporte;
use App\Models\EstadoSolicitud;
use App\Models\Municipio;
use App\Models\Notificacion;
use App\Models\PerfilProfesional;
use App\Models\Reporte;
use App\Models\SolicitudHabilitacion;
use App\Models\TipoNotificacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificacionAutomaticaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['SOLICITUD', 'CONTRATACION', 'PUBLICACION', 'SISTEMA', 'REPORTE'] as $tipo) {
            TipoNotificacion::firstOrCreate(['tipo_notificacion' => $tipo]);
        }

        foreach (['Pendiente', 'Aceptada', 'Rechazada', 'En proceso', 'Finalizada', 'Cancelada'] as $estado) {
            EstadoContratacion::firstOrCreate(['estado_contratacion' => $estado]);
        }

        foreach (['Pendiente', 'En revisión', 'Aceptada', 'Rechazada'] as $estado) {
            EstadoSolicitud::firstOrCreate(['estado_solicitud' => $estado]);
        }

        EstadoReporte::firstOrCreate(['estado_reporte' => 'Pendiente']);
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

    private function crearPerfil(User $usuario): PerfilProfesional
    {
        $sufijo = bin2hex(random_bytes(4));
        $estado = Estado::create(['estado' => 'Estado ' . $sufijo, 'activo' => true]);
        $municipio = Municipio::create([
            'id_estado' => $estado->id_estado,
            'municipio' => 'Municipio ' . $sufijo,
            'activo' => true,
        ]);

        $perfil = PerfilProfesional::create([
            'id_usuario' => $usuario->id_usuario,
            'id_municipio' => $municipio->id_municipio,
            'descripcion' => 'Perfil profesional para eventos de notificación.',
        ]);
        $perfil->activo = true;
        $perfil->save();
        return $perfil;
    }

    private function tipo(string $nombre): int
    {
        return (int) TipoNotificacion::query()
            ->where('tipo_notificacion', $nombre)
            ->value('id_tipo_notificacion');
    }

    public function test_contratacion_creada_notifica_al_trabajador(): void
    {
        $cliente = $this->crearUsuario('cliente-crea@test.com');
        $usuarioTrabajador = $this->crearUsuario('trabajador-crea@test.com');
        $perfil = $this->crearPerfil($usuarioTrabajador);

        Contratacion::create([
            'id_cliente' => $cliente->id_usuario,
            'id_trabajador' => $perfil->id_trabajador,
            'id_estado_contratacion' => 1,
            'descripcion_servicio' => 'Instalación eléctrica de prueba.',
        ]);

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $usuarioTrabajador->id_usuario,
            'id_tipo_notificacion' => $this->tipo('CONTRATACION'),
            'titulo' => 'Nueva solicitud de contratación',
            'leida' => false,
        ]);
        $this->assertDatabaseMissing('notificaciones', [
            'id_usuario' => $cliente->id_usuario,
            'titulo' => 'Nueva solicitud de contratación',
        ]);
    }

    public function test_transiciones_de_contratacion_notifican_a_los_destinatarios_del_contrato(): void
    {
        $cliente = $this->crearUsuario('cliente-transiciones@test.com');
        $usuarioTrabajador = $this->crearUsuario('trabajador-transiciones@test.com');
        $perfil = $this->crearPerfil($usuarioTrabajador);

        $aceptada = Contratacion::create([
            'id_cliente' => $cliente->id_usuario,
            'id_trabajador' => $perfil->id_trabajador,
            'id_estado_contratacion' => 1,
            'descripcion_servicio' => 'Servicio aceptado.',
        ]);
        $aceptada->update(['id_estado_contratacion' => 2]);
        $aceptada->update(['id_estado_contratacion' => 4]);
        $aceptada->update(['id_estado_contratacion' => 5]);

        $rechazada = Contratacion::create([
            'id_cliente' => $cliente->id_usuario,
            'id_trabajador' => $perfil->id_trabajador,
            'id_estado_contratacion' => 1,
            'descripcion_servicio' => 'Servicio rechazado.',
        ]);
        $rechazada->update(['id_estado_contratacion' => 3]);

        $cancelada = Contratacion::create([
            'id_cliente' => $cliente->id_usuario,
            'id_trabajador' => $perfil->id_trabajador,
            'id_estado_contratacion' => 1,
            'descripcion_servicio' => 'Servicio cancelado.',
        ]);
        $cancelada->update(['id_estado_contratacion' => 6]);

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $cliente->id_usuario,
            'id_tipo_notificacion' => $this->tipo('CONTRATACION'),
            'titulo' => 'Contratación aceptada',
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $cliente->id_usuario,
            'titulo' => 'Contratación rechazada',
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $cliente->id_usuario,
            'titulo' => 'Contratación finalizada',
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $usuarioTrabajador->id_usuario,
            'titulo' => 'Contratación finalizada',
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $cliente->id_usuario,
            'titulo' => 'Contratación cancelada',
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $usuarioTrabajador->id_usuario,
            'titulo' => 'Contratación cancelada',
        ]);
    }

    public function test_solicitud_enviada_aprobada_y_rechazada_notifica_al_usuario(): void
    {
        $usuario = $this->crearUsuario('solicitud-notificacion@test.com');
        $perfil = $this->crearPerfil($usuario);

        $aceptada = SolicitudHabilitacion::create([
            'id_trabajador' => $perfil->id_trabajador,
        ]);
        $aceptada->id_estado_solicitud = 3;
        $aceptada->save();

        $rechazada = SolicitudHabilitacion::create([
            'id_trabajador' => $perfil->id_trabajador,
        ]);
        $rechazada->id_estado_solicitud = 4;
        $rechazada->save();

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $usuario->id_usuario,
            'id_tipo_notificacion' => $this->tipo('SOLICITUD'),
            'titulo' => 'Solicitud de habilitación enviada',
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $usuario->id_usuario,
            'titulo' => 'Solicitud de habilitación aprobada',
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $usuario->id_usuario,
            'titulo' => 'Solicitud de habilitación rechazada',
        ]);
    }

    public function test_reporte_de_publicacion_o_perfil_notifica_a_administradores_activos(): void
    {
        $adminUno = $this->crearUsuario('admin-uno@test.com', true, true);
        $adminDos = $this->crearUsuario('admin-dos@test.com', true, true);
        $adminInactivo = $this->crearUsuario('admin-inactivo-reporte@test.com', true, false);
        $denunciante = $this->crearUsuario('denunciante@test.com');

        Reporte::create([
            'id_denunciante' => $denunciante->id_usuario,
            'tipo_objetivo' => 'PERFIL',
            'id_objetivo' => 123,
            'motivo' => 'Información incorrecta',
            'descripcion' => 'Reporte utilizado para probar la notificación administrativa.',
            'id_estado_reporte' => 1,
            'fecha_creacion' => now(),
        ]);

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $adminUno->id_usuario,
            'id_tipo_notificacion' => $this->tipo('REPORTE'),
            'titulo' => 'Nuevo perfil reportado',
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $adminDos->id_usuario,
            'titulo' => 'Nuevo perfil reportado',
        ]);
        $this->assertDatabaseMissing('notificaciones', [
            'id_usuario' => $adminInactivo->id_usuario,
            'titulo' => 'Nuevo perfil reportado',
        ]);
        $this->assertDatabaseMissing('notificaciones', [
            'id_usuario' => $denunciante->id_usuario,
            'titulo' => 'Nuevo perfil reportado',
        ]);
    }
}
