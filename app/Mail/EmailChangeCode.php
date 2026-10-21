<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EmailChangeCode extends Mailable
{
    public function __construct(public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Kode verifikasi perubahan email'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.email-change-code');
    }
}
