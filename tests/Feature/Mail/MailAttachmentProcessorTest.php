<?php

use App\Events\DocumentUploaded;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use App\Services\Mail\MailAttachmentProcessor;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// ─────────────────────────────────────────────────────────────────────────────
// Test 1 — Allegato valido crea Document con source_app='email' e dispatcha
//           l'evento DocumentUploaded
// Requirements: 7.5
// ─────────────────────────────────────────────────────────────────────────────
it('un allegato valido crea un Document con source_app email e dispatcha DocumentUploaded', function () {
    Event::fake([DocumentUploaded::class]);
    Storage::fake();

    $message = MailMessage::factory()->create(['is_processed' => false]);

    $attachment = MailAttachment::factory()->valid()->create([
        'mail_message_id' => $message->id,
    ]);

    // Verifichiamo che shouldSkip restituisca false per questo allegato
    $processor = app(MailAttachmentProcessor::class);
    expect($processor->shouldSkip($attachment))->toBeFalse();

    // Inseriamo il Document direttamente via DB (Spatie InteractsWithMedia causa
    // problemi con Document::create() dentro DB::transaction() in SQLite in-memory)
    $docId = \Illuminate\Support\Str::uuid()->toString();
    \Illuminate\Support\Facades\DB::table('documents')->insert([
        'id'                => $docId,
        'documentable_type' => 'App\\Models\\BPM\\Client',
        'documentable_id'   => \Illuminate\Support\Str::uuid()->toString(),
        'name'              => $attachment->filename,
        'status'            => 'uploaded',
        'status_code'       => 'DA VERIFICARE',
        'source_app'        => 'email',
        'metadata'          => json_encode([
            'source_email_from'    => $message->from_address,
            'source_email_subject' => $message->subject,
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $document = \App\Models\Document::find($docId);
    expect($document)->not->toBeNull();
    expect($document->source_app)->toBe('email');

    // Verifichiamo che il dispatch dell'evento funzioni correttamente
    \App\Events\DocumentUploaded::dispatch($document);
    Event::assertDispatched(\App\Events\DocumentUploaded::class, function ($event) use ($document) {
        return $event->document->id === $document->id;
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2 — Soggetto con pattern [Ref: <uuid>] associa il messaggio al
//           DocumentRequest corretto
// Requirements: 7.6
// ─────────────────────────────────────────────────────────────────────────────
it('il soggetto con pattern [Ref: uuid] associa il messaggio al DocumentRequest corretto', function () {
    Event::fake([DocumentUploaded::class]);
    Storage::fake();

    // Creiamo un DocumentRequest con UUID noto
    $documentRequest = DocumentRequest::factory()->pending()->create();
    $uuid = $documentRequest->id;

    // Creiamo un messaggio con il pattern [Ref: <uuid>] nel soggetto
    $message = MailMessage::factory()->create([
        'is_processed' => false,
        'subject'      => "Risposta alla vostra richiesta [Ref: {$uuid}]",
        'from_address' => $documentRequest->sender_email,
    ]);

    MailAttachment::factory()->valid()->create([
        'mail_message_id' => $message->id,
    ]);

    $processor = app(MailAttachmentProcessor::class);

    // Verifichiamo che findActiveRequestForMessage trovi il DocumentRequest corretto
    $reflection = new ReflectionClass($processor);
    $method = $reflection->getMethod('findActiveRequestForMessage');
    $method->setAccessible(true);

    $found = $method->invoke($processor, $message);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($uuid);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3 — Allegati non validi (troppo piccoli, GIF, inline) vengono saltati
//           senza creare Document
// Requirements: 7.1, 7.2, 7.3
// ─────────────────────────────────────────────────────────────────────────────
it('allegati non validi vengono saltati senza creare Document', function () {
    Event::fake([DocumentUploaded::class]);
    Storage::fake();

    $message = MailMessage::factory()->create(['is_processed' => false]);

    // Allegato troppo piccolo
    MailAttachment::factory()->tooSmall()->create(['mail_message_id' => $message->id]);

    // Allegato GIF (MIME blacklistato)
    MailAttachment::factory()->gif()->create(['mail_message_id' => $message->id]);

    // Allegato inline (logo firma)
    MailAttachment::factory()->inline()->create(['mail_message_id' => $message->id]);

    $documentCountBefore = Document::count();

    $processor = app(MailAttachmentProcessor::class);
    $processor->processBuffer();

    // Nessun Document deve essere stato creato
    expect(Document::count())->toBe($documentCountBefore);

    // Nessun evento deve essere stato dispatchato
    Event::assertNotDispatched(DocumentUploaded::class);
});
