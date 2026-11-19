<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BienvenidaUsuario extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('¡Bienvenido a TrabajITO!')
        ->view(
            ['emails.trabajito', 'emails.trabajito-text'],
            [
                'nombre' => $notifiable->nombre,
                'esSeguridad' => false,
            ]
        );
}
}