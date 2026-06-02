<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminThirdFactorRequested extends Notification
{
    use Queueable;

    public function __construct(
        public User $requester,
        public int $minutes = 15
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

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