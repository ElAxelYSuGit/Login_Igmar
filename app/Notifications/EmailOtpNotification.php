<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $minutes = 10
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

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