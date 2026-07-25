<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerResetPassword extends Notification
{
    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('shop.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Restablece tu contraseña — Equiterm Industries')
            ->greeting('Hola ' . $notifiable->first_name . ',')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line('Este enlace expira en 60 minutos.')
            ->line('Si no solicitaste el cambio, puedes ignorar este correo — tu contraseña actual sigue siendo válida.')
            ->salutation('— Equiterm Industries');
    }
}
