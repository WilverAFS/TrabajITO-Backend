<?php

namespace App\Observers;

use App\Models\Contratacion;
use App\Models\PerfilProfesional;
use App\Services\NotificacionService;

class ContratacionObserver
{
    public function created(Contratacion $contratacion): void
    {
        $idUsuarioTrabajador = PerfilProfesional::query()
            ->whereKey($contratacion->id_trabajador)
            ->value('id_usuario');

        if ($idUsuarioTrabajador) {
            app(NotificacionService::class)->crear(
                (int) $idUsuarioTrabajador,
                'CONTRATACION',
                'Nueva solicitud de contratación',
                'Has recibido una nueva solicitud de contratación.'
            );
        }
    }

    public function updated(Contratacion $contratacion): void
    {
        if (! $contratacion->wasChanged('id_estado_contratacion')) {
            return;
        }

        $idCliente = (int) $contratacion->id_cliente;
        $idUsuarioTrabajador = PerfilProfesional::query()
            ->whereKey($contratacion->id_trabajador)
            ->value('id_usuario');

        $servicio = app(NotificacionService::class);

        switch ((int) $contratacion->id_estado_contratacion) {
            case 2:
                $servicio->crear(
                    $idCliente,
                    'CONTRATACION',
                    'Contratación aceptada',
                    'El trabajador aceptó tu solicitud de contratación.'
                );
                break;

            case 3:
                $servicio->crear(
                    $idCliente,
                    'CONTRATACION',
                    'Contratación rechazada',
                    'El trabajador rechazó tu solicitud de contratación.'
                );
                break;

            case 5:
                $this->notificarAmbos(
                    $servicio,
                    $idCliente,
                    $idUsuarioTrabajador,
                    'Contratación finalizada',
                    'La contratación ha sido finalizada.'
                );
                break;

            case 6:
                $this->notificarAmbos(
                    $servicio,
                    $idCliente,
                    $idUsuarioTrabajador,
                    'Contratación cancelada',
                    'La contratación ha sido cancelada.'
                );
                break;
        }
    }

    private function notificarAmbos(
        NotificacionService $servicio,
        int $idCliente,
        mixed $idUsuarioTrabajador,
        string $titulo,
        string $mensaje
    ): void {
        $servicio->crear(
            $idCliente,
            'CONTRATACION',
            $titulo,
            $mensaje
        );

        if ($idUsuarioTrabajador) {
            $servicio->crear(
                (int) $idUsuarioTrabajador,
                'CONTRATACION',
                $titulo,
                $mensaje
            );
        }
    }
}
