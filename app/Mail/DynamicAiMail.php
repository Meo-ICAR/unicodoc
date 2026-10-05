<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DynamicAiMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Creiamo una nuova istanza del messaggio usando la
     * Constructor Property Promotion per assegnare direttamente le variabili.
     */
    public function __construct(
        public string $aiSubject,
        public string $aiBody
    ) {}

    /**
     * Definisce l'oggetto dell'email e i metadati.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->aiSubject,
            // tags: ['ai-generated', 'unicodoc-compliance'], // Utile se usi Mailgun/Postmark
        );
    }

    /**
     * Definisce il contenuto del messaggio.
     */
    public function content(): Content
    {
        return new Content(
            // Opzione A: Usiamo una vista Blade per includere header/footer aziendali
            view: 'emails.dynamic-ai',
            // Opzione B (Alternativa): Invia direttamente la stringa HTML dell'AI senza template
            // htmlString: $this->aiBody,
        );
    }

    /**
     * Gestione degli allegati (Opzionale).
     * Se in futuro vorrai che l'AI alleghi automaticamente il PDF della ricevuta.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
