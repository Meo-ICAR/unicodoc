<?php

use App\Models\DocumentRequest;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use App\Services\Mail\MailAttachmentProcessor;
use App\Services\Mail\MailMessageProcessor;
use Illuminate\Support\Facades\Http;

// ─────────────────────────────────────────────────────────────────────────────
// Helper: crea un messaggio con body lungo e un DocumentRequest associato
// ─────────────────────────────────────────────────────────────────────────────
function makeMessageWithRequest(string $body = 'Questo è un messaggio di testo abbastanza lungo'): array
{
    $documentRequest = DocumentRequest::factory()->pending()->create();

    $message = MailMessage::factory()->create([
        'is_processed' => false,
        'body_text'    => $body,
        'subject'      => "Domanda [Ref: {$documentRequest->id}]",
        'from_address' => $documentRequest->sender_email,
    ]);

    return [$documentRequest, $message];
}

// ─────────────────────────────────────────────────────────────────────────────
// Test 1 — Messaggio senza allegati validi e body_text > 20 char →
//           has_unread_messages=true sul DocumentRequest
// Requirements: 8.1
// ─────────────────────────────────────────────────────────────────────────────
it('imposta has_unread_messages=true sul DocumentRequest quando body_text > 20 char', function () {
    Http::fake(['*' => Http::response([], 200)]);

    config(['services.bpm.webhook_url' => 'https://bpm.example.com/webhook']);

    [$documentRequest, $message] = makeMessageWithRequest('Questo è un messaggio di testo abbastanza lungo');

    $processor = app(MailMessageProcessor::class);
    $processor->processUnreadMessages();

    $documentRequest->refresh();
    expect($documentRequest->has_unread_messages)->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2 — Stesso caso → last_message_received aggiornato con testo ripulito
//           dall'HTML
// Requirements: 8.2
// ─────────────────────────────────────────────────────────────────────────────
it('aggiorna last_message_received con il testo ripulito dall HTML', function () {
    Http::fake(['*' => Http::response([], 200)]);

    config(['services.bpm.webhook_url' => 'https://bpm.example.com/webhook']);

    $htmlBody = '<p>Questo è un <strong>messaggio</strong> con HTML</p>';
    [$documentRequest, $message] = makeMessageWithRequest($htmlBody);

    $processor = app(MailMessageProcessor::class);
    $processor->processUnreadMessages();

    $documentRequest->refresh();
    expect($documentRequest->last_message_received)->toBe(strip_tags($htmlBody));
    expect($documentRequest->last_message_received)->not->toContain('<p>');
    expect($documentRequest->last_message_received)->not->toContain('<strong>');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3 — Stesso caso → inviata richiesta POST al webhook BPM
// Requirements: 8.3
// ─────────────────────────────────────────────────────────────────────────────
it('invia una richiesta POST al webhook BPM configurato', function () {
    $webhookUrl = 'https://bpm.example.com/webhook';
    Http::fake([$webhookUrl => Http::response([], 200)]);

    config(['services.bpm.webhook_url' => $webhookUrl]);

    [$documentRequest, $message] = makeMessageWithRequest('Questo è un messaggio di testo abbastanza lungo');

    $processor = app(MailMessageProcessor::class);
    $processor->processUnreadMessages();

    Http::assertSent(function ($request) use ($webhookUrl) {
        return $request->url() === $webhookUrl
            && $request->method() === 'POST'
            && $request['event'] === 'document_request_question_received';
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 4 — Messaggio processato → is_processed=true sul MailMessage
// Requirements: 8.4
// ─────────────────────────────────────────────────────────────────────────────
it('imposta is_processed=true sul MailMessage dopo il processing', function () {
    Http::fake(['*' => Http::response([], 200)]);

    config(['services.bpm.webhook_url' => 'https://bpm.example.com/webhook']);

    [$documentRequest, $message] = makeMessageWithRequest('Questo è un messaggio di testo abbastanza lungo');

    expect($message->is_processed)->toBeFalse();

    $processor = app(MailMessageProcessor::class);
    $processor->processUnreadMessages();

    $message->refresh();
    expect($message->is_processed)->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 5 — body_text <= 20 char → DocumentRequest non aggiornato, webhook
//           non inviato
// Requirements: 8.5
// ─────────────────────────────────────────────────────────────────────────────
it('non aggiorna il DocumentRequest e non invia webhook quando body_text <= 20 char', function () {
    Http::fake(['*' => Http::response([], 200)]);

    config(['services.bpm.webhook_url' => 'https://bpm.example.com/webhook']);

    // Body di esattamente 10 caratteri (< 20)
    [$documentRequest, $message] = makeMessageWithRequest('Ciao!');

    $originalUnread = $documentRequest->has_unread_messages;
    $originalLastMessage = $documentRequest->last_message_received;

    $processor = app(MailMessageProcessor::class);
    $processor->processUnreadMessages();

    $documentRequest->refresh();

    // Il DocumentRequest non deve essere stato aggiornato
    expect($documentRequest->has_unread_messages)->toBeFalse();
    expect($documentRequest->last_message_received)->toBeNull();

    // Nessuna richiesta HTTP deve essere stata inviata
    Http::assertNothingSent();
});
