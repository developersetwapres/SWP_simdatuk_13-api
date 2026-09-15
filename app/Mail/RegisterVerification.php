<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegisterVerification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Request $request) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verifikasi Email');
    }

    public function content(): Content
    {
        return new Content(
            view: 'auth.register-verification',
            with: [
                'name' => $this->request->name,
                'username' => $this->request->username,
                'password' => $this->request->password,
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
