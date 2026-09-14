<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPassword extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Request $request) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset Password');
    }

    public function content(): Content
    {
        return new Content(
            view: 'auth.forgot-password',
            with: ['code' => $this->request->verification_code],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
