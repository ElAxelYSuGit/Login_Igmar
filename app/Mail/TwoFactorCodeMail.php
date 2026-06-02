<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;

    // Recibimos el código desde el controlador
    public function __construct($code)
    {
        $this->code = $code;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de acceso 2FA',
        );
    }

    public function content(): Content
    {
        // Apuntamos a la vista que crearemos en el siguiente paso
        return new Content(
            view: 'emails.two_factor_code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}