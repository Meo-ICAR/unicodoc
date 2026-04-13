<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Enums\SyncStatus;
use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessDocumentAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Il parametro $tries imposta quanti tentativi può fare il job per non failare permanentemente
     */
    public int $tries = 3;

    /**
     * Il timeout in secondi del job prima di essere ucciso dal worker CLI
     */
    public int $timeout = 60;

    public function __construct(
        public Document $document
    ) {}

    /**
     * Esegue il processo di validazione e arricchimento dati
     */
    public function handle(): void
    {
        // Recupero path di rete dal cloud / invio stream
        $media = $this->document->getFirstMedia('documents');

        Log::info("Inizio elaborazione AI Job documento " . $this->document->id);

        try {
            // ==============================================================
            // TODO: Inserisci qui l'invio e consumazione reale del file tramite OCR / LLM API.
            // Esempio fittizio:
            // $extractedText = TextractService::analyze($media->getPath());
            // $aiPayload = OpenAiService::extractStructure($extractedText);
            // ==============================================================

            // Simuliamo il ritardo dell'OCR
            sleep(2);

            // Mockiamo i metadati estratti
            $mockedMetadata = [
                'fiscal_code'  => 'RSSMRA80A01H501U',
                'amount'       => 1500.50,
                'issue_date'   => '2026-04-01',
                'confidence'   => 0.98,
                'document_type'=> 'Fattura'
            ];

            // 1. Aggiorniamo stato e metadata al completamento
            $this->document->update([
                'status'   => DocumentStatus::VERIFIED,
                'metadata' => $mockedMetadata,
            ]);

            // 2. Invio del Webhook al servizio chiamante.
            // Nello scenario M2M, possiamo mappare su un endpoint o su campi presenti nella `company`
            $this->sendWebhookNotification();

        } catch (Throwable $e) {
            // In caso di errore fallimentare e definitvo aggiorna il target state
            $this->document->update([
                'status' => DocumentStatus::FAILED,
            ]);

            Log::error("Document Process Failed", [
                'doc_id' => $this->document->id,
                'error' => $e->getMessage()
            ]);

            // Ri-Lancia per forzare il retry nativo di Horizon finchè tries non si esaurisce
            throw $e;
        }
    }

    /**
     * Esegue un server-to-server payload notificando del completamento.
     */
    private function sendWebhookNotification(): void
    {
        // Esempio: Recuperiamo l'URL del webhook dal tenant/company, oppure dall'ENV o da configs.
        // Assumo una relazione come: $this->document->company->webhook_url
        $webhookUrl = $this->document->company?->webhook_url ?? config('services.calling_app.webhook_url');

        if (!$webhookUrl) {
            Log::warning('Nessun URL Webhook trovato per notifica su documento: ' . $this->document->id);
            return;
        }

        $payload = [
            'event'       => 'document.processed',
            'document_id' => $this->document->id,
            'status'      => $this->document->status->value,
            'metadata'    => $this->document->metadata,
        ];

        // L'utilizzo di `timeout(10)` e `retry(3, 200)` rende questo request incredibilmente robusto
        try {
            $response = Http::timeout(10)
                ->retry(3, 200) // Riprova 3 volte, aspettando 200ms
                ->withHeaders([
                    // Possibile utilizzo di signature HMAC per sicurezza (es. X-Signature)
                    'X-DMS-Signature' => hash_hmac('sha256', json_encode($payload), config('app.key')),
                ])
                ->post($webhookUrl, $payload);

            // Aggiorna lo status del Webhook a seconda del successo o meno
            $this->document->update([
                'sync_status' => $response->successful() ? SyncStatus::SYNCED : SyncStatus::FAILED,
            ]);

            if ($response->failed()) {
                Log::error("Webhook fallito per il doc: {$this->document->id}", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->document->update([
                'sync_status' => SyncStatus::FAILED,
            ]);

            Log::error("Webhook fallito per il doc: {$this->document->id}", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
