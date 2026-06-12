<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación de código OTP por correo electrónico.
 *
 * Envía un correo al usuario con el código de verificación de
 * 6 dígitos generado durante el segundo factor de autenticación (2FA).
 * El código tiene un tiempo de expiración configurable.
 *
 * @package App\Notifications
 * @standard PHPDoc (PSR-5)
 */
class EmailOtpNotification extends Notification
{
    use Queueable;

    /**
     * Crea una nueva instancia de la notificación OTP.
     *
     * @param  string  $code     Código OTP de 6 dígitos (texto plano).
     * @param  int     $minutes  Minutos de validez del código (por defecto 10).
     */
    public function __construct(
        public string $code,
        public int $minutes = 10
    ) {}

    /**
     * Define los canales de entrega de la notificación.
     *
     * @param  mixed  $notifiable  La entidad que recibe la notificación (User).
     * @return array<int, string>  Canales habilitados (solo 'mail').
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construye el contenido del correo electrónico con el código OTP.
     *
     * @param  mixed  $notifiable  La entidad que recibe el correo (User).
     * @return MailMessage          El mensaje de correo formateado.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu código de verificación')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu código de verificación es: ' . $this->code)
            ->line('Este código vence en ' . $this->minutes . ' minutos.')
            ->line('Si no solicitaste este acceso, ignora este correo.');
    }
}