<?php

namespace App\Services\Mail;

use App\Models\DocumentRequest;
use App\Models\MailMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailMessageProcessor
{
    public function __construct(
        protected MailAttachmentProcessor $attachmentProcessor
    ) {}

    public function processUnreadMessages(): void
    {
        // Prendiamo i messaggi non ancora processati
        $messages = MailMessage::where('is_processed', false)->with('attachments')->get();

        foreach ($messages as $message) {
            // 1. Cerchiamo a quale Dossier è legata la mail (usando la funzione già scritta)
            $activeRequest = $this->attachmentProcessor->findActiveRequestForMessage($message);

            if ($activeRequest) {
                // 2. Contiamo gli allegati "validi" (escludendo firme e loghi)
                $validAttachments = $message->attachments->filter(function ($att) {
                    return !$this->attachmentProcessor->shouldSkip($att);
                });

                // 3. LA LOGICA: Se non ci sono documenti, ma c'è un testo lungo (es. > 20 caratteri)
                if ($validAttachments->isEmpty() && strlen(trim(strip_tags($message->body_text))) > 20) {
                    $this->handleQuestion($activeRequest, $message);
                } else {
                    // Se ci sono allegati, il MailAttachmentProcessor farà il suo lavoro
                    // e classificherà i file.
                }
            }

            // Segniamo il messaggio madre come processato
            $message->update(['is_processed' => true]);
        }
    }

    protected function handleQuestion(DocumentRequest $request, MailMessage $message): void
    {
        // 1. Aggiorniamo il Dossier in UnicoDoc
        $request->update([
            'has_unread_messages' => true,
            'last_message_received' => strip_tags($message->body_text),  // Rimuoviamo l'HTML
        ]);

        // 2. Avvisiamo il BPM tramite Webhook!
        try {
            Http::post(config('services.bpm.webhook_url'), [
                'event' => 'document_request_question_received',
                'bpm_task_id' => $request->bpm_task_id,
                'documentable_id' => $request->documentable_id,
                'message_content' => $request->last_message_received,
                'sender' => $message->from_address
            ]);
        } catch (\Exception $e) {
            Log::error("Impossibile notificare il BPM della domanda per la request {$request->id}");
        }
    }
}
