<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación al admin primario de solicitud de identidad pendiente.
 *
 * Se envía al administrador primario cuando un admin nuevo completa
 * el segundo factor (OTP) y necesita verificación de identidad
 * presencial como tercer factor de autenticación (3FA).
 *
 * @package App\Notifications
 * @standard PHPDoc (PSR-5)
 */
class AdminThirdFactorRequested extends Notification
{
    use Queueable;

    /**
     * Crea una nueva instancia de la notificación de 3FA.
     *
     * @param  User  $requester  El admin que solicita la verificación.
     * @param  int   $minutes    Minutos antes de que expire la solicitud (por defecto 15).
     */
    public function __construct(
        public User $requester,
        public int $minutes = 15
    ) {}

    /**
     * Define los canales de entrega de la notificación.
     *
     * @param  mixed  $notifiable  La entidad que recibe la notificación (admin primario).
     * @return array<int, string>  Canales habilitados (solo 'mail').
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construye el correo electrónico para el admin primario.
     *
     * Incluye nombre, correo del solicitante y tiempo de expiración.
     *
     * @param  mixed  $notifiable  El admin primario que recibe el correo.
     * @return MailMessage          El mensaje de correo formateado.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Solicitud pendiente de verificación de identidad')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Hay una solicitud de acceso pendiente para: ' . $this->requester->name)
            ->line('Correo del solicitante: ' . $this->requester->email)
            ->line('La solicitud vence en ' . $this->minutes . ' minutos.')
            ->line('Revisa el código de identidad mostrado al solicitante.');
    }
}