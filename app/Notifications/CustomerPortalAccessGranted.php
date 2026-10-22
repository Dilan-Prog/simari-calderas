<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al cliente de que su acceso al Portal de servicios ya está activo.
 * NUNCA incluye la contraseña: el equipo se la comparte por otro medio.
 */
class CustomerPortalAccessGranted extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $supportEmail = Setting::get('footer.email', 'administracion@equitermindustries.com.mx');
        $supportPhone = Setting::get('footer.phone', '+52 1 449 457 7320');

        return (new MailMessage)
            ->subject('Tu acceso al Portal de servicios — Equiterm Industries')
            ->greeting('Hola ' . $notifiable->first_name . ',')
            ->line('Tu acceso al Portal de servicios de Equiterm Industries ya está activo.')
            ->line('Inicia sesión con este correo: ' . $notifiable->email)
            ->action('Iniciar sesión', route('shop.login'))
            ->line('Tu contraseña te fue compartida por separado por nuestro equipo. Si no la tienes o no la recuerdas, contáctanos.')
            ->line('¿Dudas? Escríbenos a ' . $supportEmail . ' o por WhatsApp al ' . $supportPhone . '.')
            ->salutation('— Equiterm Industries');
    }
}
