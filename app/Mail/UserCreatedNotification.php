<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $user; // Déclarez une propriété publique pour l'utilisateur

    public function __construct($user)
    {
        $this->user = $user; // Assignez l'utilisateur à la propriété
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'User Created Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
{
    return new Content(
        view: 'emails.user-created', // Utilisez le chemin de votre vue
    );
}

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
