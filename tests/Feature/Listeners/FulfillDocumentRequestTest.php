<?php

use App\Events\DocumentClassified;
use App\Listeners\FulfillDocumentRequest;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// ─────────────────────────────────────────────────────────────────────────────
// Test 1 — Documento classificato corrispondente → fulfilled_by_document_id
//           impostato sull'item corretto
// Requirements: 12.1
// ─────────────────────────────────────────────────────────────────────────────
it('imposta fulfilled_by_document_id sull item corretto quando il documento corrisponde', function () {
    Http::fake(['*' => Http::response([], 200)]);
    config(['services.bpm.webhook_url' => 'https://bpm.example.com/webhook']);

    $documentType = DocumentType::factory()->create();
    $entityId = Str::uuid()->toString();
    $entityType = 'App\\Models\\BPM\\Client';

    $request = DocumentRequest::factory()->pending()->create([
        'documentable_type' => $entityType,
        'documentable_id'   => $entityId,
    ]);

    $item = DocumentRequestItem::factory()->create([
        'document_request_id'      => $request->id,
        'document_type_id'         => $documentType->id,
        'fulfilled_by_document_id' => null,
    ]);

    $document = Document::factory()->create([
        'document_type_id'  => $documentType->id,
        'documentable_type' => $entityType,
        'documentable_id'   => $entityId,
    ]);

    $event = new DocumentClassified($document);
    $listener = app(FulfillDocumentRequest::class);
    $listener->handle($event);

    $item->refresh();
    expect($item->fulfilled_by_document_id)->toBe($document->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2 — Tutti gli item soddisfatti → stato del Dossier diventa 'COMPLETED'
// Requirements: 12.2
// ─────────────────────────────────────────────────────────────────────────────
it('aggiorna lo stato del Dossier a COMPLETED quando tutti gli item sono soddisfatti', function () {
    Http::fake(['*' => Http::response([], 200)]);
    config(['services.bpm.webhook_url' => 'https://bpm.example.com/webhook']);

    $documentType = DocumentType::factory()->create();
    $entityId = Str::uuid()->toString();
    $entityType = 'App\\Models\\BPM\\Client';

    $request = DocumentRequest::factory()->pending()->create([
        'documentable_type' => $entityType,
        'documentable_id'   => $entityId,
        'bpm_task_id'       => 'task-123',
    ]);

    // Un solo item pendente
    DocumentRequestItem::factory()->create([
        'document_request_id'      => $request->id,
        'document_type_id'         => $documentType->id,
        'fulfilled_by_document_id' => null,
    ]);

    $document = Document::factory()->create([
        'document_type_id'  => $documentType->id,
        'documentable_type' => $entityType,
        'documentable_id'   => $entityId,
    ]);

    $event = new DocumentClassified($document);
    $listener = app(FulfillDocumentRequest::class);
    $listener->handle($event);

    $request->refresh();
    expect($request->status)->toBe('COMPLETED');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3 — Almeno un item non soddisfatto → stato del Dossier diventa 'PARTIAL'
// Requirements: 12.3
// ─────────────────────────────────────────────────────────────────────────────
it('aggiorna lo stato del Dossier a PARTIAL quando almeno un item rimane non soddisfatto', function () {
    Http::fake(['*' => Http::response([], 200)]);

    $documentType1 = DocumentType::factory()->create();
    $documentType2 = DocumentType::factory()->create();
    $entityId = Str::uuid()->toString();
    $entityType = 'App\\Models\\BPM\\Client';

    $request = DocumentRequest::factory()->pending()->create([
        'documentable_type' => $entityType,
        'documentable_id'   => $entityId,
    ]);

    // Item che verrà soddisfatto
    DocumentRequestItem::factory()->create([
        'document_request_id'      => $request->id,
        'document_type_id'         => $documentType1->id,
        'fulfilled_by_document_id' => null,
    ]);

    // Item che rimarrà pendente
    DocumentRequestItem::factory()->create([
        'document_request_id'      => $request->id,
        'document_type_id'         => $documentType2->id,
        'fulfilled_by_document_id' => null,
    ]);

    $document = Document::factory()->create([
        'document_type_id'  => $documentType1->id,
        'documentable_type' => $entityType,
        'documentable_id'   => $entityId,
    ]);

    $event = new DocumentClassified($document);
    $listener = app(FulfillDocumentRequest::class);
    $listener->handle($event);

    $request->refresh();
    expect($request->status)->toBe('PARTIAL');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 4 — Documento senza document_type_id → nessun DocumentRequest modificato
// Requirements: 12.4
// ─────────────────────────────────────────────────────────────────────────────
it('non modifica alcun DocumentRequest quando il documento non ha document_type_id', function () {
    Http::fake();

    $entityId = Str::uuid()->toString();
    $entityType = 'App\\Models\\BPM\\Client';

    $request = DocumentRequest::factory()->pending()->create([
        'documentable_type' => $entityType,
        'documentable_id'   => $entityId,
    ]);

    $document = Document::factory()->create([
        'document_type_id'  => null,
        'documentable_type' => $entityType,
        'documentable_id'   => $entityId,
    ]);

    $event = new DocumentClassified($document);
    $listener = app(FulfillDocumentRequest::class);
    $listener->handle($event);

    $request->refresh();
    // Lo stato non deve essere cambiato
    expect($request->status)->toBe('PENDING');

    // Nessuna richiesta HTTP inviata
    Http::assertNothingSent();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 5 — Dossier raggiunge COMPLETED → inviata POST al webhook BPM con
//           event='document_request_completed' e bpm_task_id
// Requirements: 12.5
// ─────────────────────────────────────────────────────────────────────────────
it('invia POST al webhook BPM con event e bpm_task_id quando il Dossier raggiunge COMPLETED', function () {
    $webhookUrl = 'https://bpm.example.com/webhook';
    Http::fake([$webhookUrl => Http::response([], 200)]);

    config(['services.bpm.webhook_url' => $webhookUrl]);

    $documentType = DocumentType::factory()->create();
    $entityId = Str::uuid()->toString();
    $entityType = 'App\\Models\\BPM\\Client';
    $bpmTaskId = 'bpm-task-456';

    $request = DocumentRequest::factory()->pending()->create([
        'documentable_type' => $entityType,
        'documentable_id'   => $entityId,
        'bpm_task_id'       => $bpmTaskId,
    ]);

    DocumentRequestItem::factory()->create([
        'document_request_id'      => $request->id,
        'document_type_id'         => $documentType->id,
        'fulfilled_by_document_id' => null,
    ]);

    $document = Document::factory()->create([
        'document_type_id'  => $documentType->id,
        'documentable_type' => $entityType,
        'documentable_id'   => $entityId,
    ]);

    $event = new DocumentClassified($document);
    $listener = app(FulfillDocumentRequest::class);
    $listener->handle($event);

    Http::assertSent(function ($request) use ($webhookUrl, $bpmTaskId) {
        return $request->url() === $webhookUrl
            && $request->method() === 'POST'
            && $request['event'] === 'document_request_completed'
            && $request['bpm_task_id'] === $bpmTaskId;
    });
});
