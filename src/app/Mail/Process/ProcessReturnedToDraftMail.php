<?php

namespace App\Mail\Process;

use App\Models\Process;
use App\Models\ProcessSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProcessReturnedToDraftMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Process $process,
        public ProcessSigner $signer,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->signer->user->email,
            from: new Address('noreply@signflow.in', 'Sign Flow'),
            subject: 'Processo digital retornado para rascunho: ' . $this->process->reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.process.returned-to-draft-mail',
            with: [
                'process' => $this->process,
                'signer' => $this->signer,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}