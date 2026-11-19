<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CambioContrasena extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('TrabajITO - Aviso de cambio de contraseña')
        ->view(
            ['emails.trabajito', 'emails.trabajito-text'],
            [
                'nombre' => $notifiable->nombre,
                'esSeguridad' => true,
            ]
        );
}
}
