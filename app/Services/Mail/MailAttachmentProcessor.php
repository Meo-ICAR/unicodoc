<?php

namespace App\Services\Mail;

use App\Events\DocumentUploaded;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\MailAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MailAttachmentProcessor
{
    // MIME types non ammessi o inutili per il business
    protected array $blacklistedMimes = [
        'image/gif',
        'application/octet-stream',
        'text/calendar'
    ];

    public function processBuffer(): void
    {
        // Prendiamo gli allegati non ancora convertiti in documenti
        $attachments = MailAttachment::whereNull('document_id')
            ->whereHas('mailMessage', fn($q) => $q->where('is_processed', false))
            ->with('mailMessage')
            ->get();

        foreach ($attachments as $attachment) {
            if ($this->shouldSkip($attachment)) {
                continue;  // Ignora loghi e firme
            }

            $this->convertToDocument($attachment);
        }

        // Segniamo i messaggi padre come processati
        DB::table('mail_messages')
            ->whereIn('id', $attachments->pluck('mail_message_id'))
            ->update(['is_processed' => true]);
    }

    public function shouldSkip(MailAttachment $attachment): bool
    {
        // 1. Skip per estensione/MIME
        if (in_array($attachment->mime_type, $this->blacklistedMimes)) {
            return true;
        }

        // 2. Skip se è un'immagine "inline" (spesso loghi aziendali nella firma)
        if ($attachment->is_inline && str_starts_with($attachment->mime_type, 'image/')) {
            return true;
        }

        // 3. Skip per dimensione (< 8 KB è quasi sempre un'icona social)
        if ($attachment->size < 8192) {
            return true;
        }

        return false;
    }

    protected function convertToDocument(MailAttachment $attachment): void
    {
        DB::transaction(function () use ($attachment) {
            $message = $attachment->mailMessage;

            // 1. Creiamo il record Document (Ancora "DA VERIFICARE")
            $document = Document::create([
                'id' => Str::uuid()->toString(),
                'documentable_type' => 'App\\Models\\BPM\\Client',
                'documentable_id' => Str::uuid()->toString(),
                'name' => $attachment->filename,
                'status' => \App\Enums\DocumentStatus::UPLOADED,
                'status_code' => 'DA VERIFICARE',  // Stato iniziale
                'source_app' => 'email',
                // Arricchiamo l'AI passando Oggetto e Mittente come metadati!
                'metadata' => [
                    'source_email_from' => $message->from_address,
                    'source_email_subject' => $message->subject,
                    'source_email_body_preview' => \Str::limit($message->body_text, 200)
                ]
            ]);

            // 2. Spostiamo il file dal Buffer alla Media Library di Spatie
            $tempPath = storage_path("app/mail_buffer/{$message->id}/{$attachment->filename}");
            if (file_exists($tempPath)) {
                $document
                    ->addMedia($tempPath)
                    ->toMediaCollection('default');
            }

            // 3. Aggiorniamo l'allegato per mantenere la tracciabilità
            $attachment->update(['document_id' => $document->id]);

            // 4. LA MAGIA: Scateniamo l'evento che attiva Regex e AI!
            // L'Orchestratore che abbiamo scritto prima intercetterà questo evento.
            DocumentUploaded::dispatch($document);
        });
    }

    /**
     * Tenta di associare la mail a una richiesta documenti aperta.
     */
    public function findActiveRequestForMessage($message): ?DocumentRequest
    {
        // A. Tentativo 1: Cerchiamo un Tag nell'oggetto (es: [Ref: a1b2c3d4])
        if (preg_match('/\[Ref:\s*([a-f0-9\-]+)\]/i', $message->subject, $matches)) {
            $shortId = $matches[1];
            $request = DocumentRequest::where('id', 'like', $shortId . '%')
                ->whereIn('status', ['PENDING', 'PARTIAL'])
                ->first();

            if ($request)
                return $request;
        }

        // B. Tentativo 2: Cerchiamo semplicemente per indirizzo email del mittente
        // Se c'è una sola richiesta aperta per questa mail, è quasi certamente lei.
        return DocumentRequest::where('sender_email', $message->from_address)
            ->whereIn('status', ['PENDING', 'PARTIAL'])
            ->latest()  // Prendiamo la più recente
            ->first();
    }
}
