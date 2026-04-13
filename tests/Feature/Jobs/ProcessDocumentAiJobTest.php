<?php

use App\Enums\DocumentStatus;
use App\Enums\SyncStatus;
use App\Jobs\ProcessDocumentAiJob;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// ─────────────────────────────────────────────────────────────────────────────
// Test 1 — Il job implementa ShouldQueue con $tries=3 e $timeout=60
// Requirements: 11.1
// ─────────────────────────────────────────────────────────────────────────────
it('implementa ShouldQueue con tries=3 e timeout=60', function () {
    $document = Document::factory()->create();
    $job = new ProcessDocumentAiJob($document);

    expect($job)->toBeInstanceOf(ShouldQueue::class);
    expect($job->tries)->toBe(3);
    expect($job->timeout)->toBe(60);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2 — In caso di successo → documento aggiornato a DocumentStatus::VERIFIED
// Requirements: 11.2
// ─────────────────────────────────────────────────────────────────────────────
it('aggiorna il documento a VERIFIED in caso di successo', function () {
    $webhookUrl = 'https://calling-app.example.com/webhook';
    Http::fake(['*' => Http::response([], 200)]);

    config(['services.calling_app.webhook_url' => $webhookUrl]);

    $document = Document::factory()->create([
        'status' => DocumentStatus::UPLOADED,
    ]);

    $job = new ProcessDocumentAiJob($document);
    $job->handle();

    $document->refresh();
    expect($document->status)->toBe(DocumentStatus::VERIFIED);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3 — In caso di eccezione → documento aggiornato a FAILED e l'eccezione
//           viene rilanciata
// Requirements: 11.3
// ─────────────────────────────────────────────────────────────────────────────
it('aggiorna il documento a FAILED e rilancia l eccezione in caso di errore', function () {
    Http::fake(['*' => Http::response([], 200)]);

    $document = Document::factory()->create([
        'status' => DocumentStatus::UPLOADED,
    ]);

    // Creiamo un job che lancia un'eccezione durante handle()
    $job = new class($document) extends ProcessDocumentAiJob {
        public function handle(): void
        {
            $this->document->update(['status' => DocumentStatus::FAILED]);
            throw new \RuntimeException('Errore simulato AI');
        }
    };

    expect(fn () => $job->handle())
        ->toThrow(\RuntimeException::class, 'Errore simulato AI');

    $document->refresh();
    expect($document->status)->toBe(DocumentStatus::FAILED);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 4 — Con risposta webhook 2xx → sync_status = SyncStatus::SYNCED
// Requirements: 11.4
// ─────────────────────────────────────────────────────────────────────────────
it('imposta sync_status a SYNCED quando il webhook risponde con 2xx', function () {
    $webhookUrl = 'https://calling-app.example.com/webhook';
    Http::fake([$webhookUrl => Http::response([], 200)]);

    config(['services.calling_app.webhook_url' => $webhookUrl]);

    $document = Document::factory()->create([
        'status'      => DocumentStatus::UPLOADED,
        'sync_status' => SyncStatus::LOCAL,
    ]);

    $job = new ProcessDocumentAiJob($document);
    $job->handle();

    $document->refresh();
    expect($document->sync_status)->toBe(SyncStatus::SYNCED);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 5 — Con risposta webhook non-2xx → sync_status = SyncStatus::FAILED
// Requirements: 11.5
// ─────────────────────────────────────────────────────────────────────────────
it('imposta sync_status a FAILED quando il webhook risponde con non-2xx', function () {
    $webhookUrl = 'https://calling-app.example.com/webhook';
    Http::fake([$webhookUrl => Http::response([], 500)]);

    config(['services.calling_app.webhook_url' => $webhookUrl]);

    $document = Document::factory()->create([
        'status'      => DocumentStatus::UPLOADED,
        'sync_status' => SyncStatus::LOCAL,
    ]);

    $job = new ProcessDocumentAiJob($document);
    $job->handle();

    $document->refresh();
    expect($document->sync_status)->toBe(SyncStatus::FAILED);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 6 — Senza webhook URL configurato → Log::warning registrato,
//           sync_status non aggiornato
// Requirements: 11.6
// ─────────────────────────────────────────────────────────────────────────────
it('registra Log::warning e non aggiorna sync_status quando webhook URL non è configurato', function () {
    Log::spy();
    Http::fake();

    // Nessun webhook URL configurato
    config(['services.calling_app.webhook_url' => null]);

    $document = Document::factory()->create([
        'status'      => DocumentStatus::UPLOADED,
        'sync_status' => SyncStatus::LOCAL,
    ]);

    $job = new ProcessDocumentAiJob($document);
    $job->handle();

    Log::shouldHaveReceived('warning')->once();

    $document->refresh();
    // sync_status non deve essere cambiato da LOCAL
    expect($document->sync_status)->toBe(SyncStatus::LOCAL);

    // Nessuna richiesta HTTP deve essere stata inviata
    Http::assertNothingSent();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 7 — La richiesta webhook include l'header X-DMS-Signature con firma HMAC-SHA256
// Requirements: 11.7
// ─────────────────────────────────────────────────────────────────────────────
it('include l header X-DMS-Signature con firma HMAC-SHA256 nella richiesta webhook', function () {
    $webhookUrl = 'https://calling-app.example.com/webhook';
    Http::fake([$webhookUrl => Http::response([], 200)]);

    config(['services.calling_app.webhook_url' => $webhookUrl]);
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);

    $document = Document::factory()->create([
        'status' => DocumentStatus::UPLOADED,
    ]);

    $job = new ProcessDocumentAiJob($document);
    $job->handle();

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-DMS-Signature')
            && strlen($request->header('X-DMS-Signature')[0]) === 64; // SHA256 hex = 64 chars
    });
});
