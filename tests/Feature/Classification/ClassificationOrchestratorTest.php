<?php

use App\Events\DocumentUploaded;
use App\Listeners\RunDocumentClassification;
use App\Models\ClassificationLog;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\Classification\AiClassifier;
use App\Services\Classification\ClassificationOrchestratorService;
use App\Services\Classification\ClassificationResult;
use App\Services\Classification\DocumentClassifierInterface;
use App\Services\Classification\RegexClassifier;
use Illuminate\Support\Facades\Cache;

// ─────────────────────────────────────────────────────────────────────────────
// Helper: crea un ClassificationResult di test
// ─────────────────────────────────────────────────────────────────────────────
function makeResult(int $typeId, int $score, string $classifier = 'regex'): ClassificationResult
{
    return new ClassificationResult(
        documentTypeId: $typeId,
        confidenceScore: $score,
        classifierUsed: $classifier,
        evidence: ['matched_string' => 'test']
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Setup comune: svuota la cache dei pattern regex prima di ogni test
// ─────────────────────────────────────────────────────────────────────────────
beforeEach(function () {
    Cache::flush();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 1 — RegexClassifier restituisce risultato valido → documento aggiornato,
//           AiClassifier NON invocato
// Requirements: 6.1
// ─────────────────────────────────────────────────────────────────────────────
it('aggiorna il documento con il tipo regex e non invoca AiClassifier', function () {
    $documentType = DocumentType::factory()->create(['min_confidence' => 70, 'allow_auto_verification' => false]);
    $document = Document::factory()->create(['document_type_id' => null]);

    $result = makeResult($documentType->id, 100, 'regex');

    $regex = $this->mock(RegexClassifier::class);
    $regex->shouldReceive('classify')->once()->andReturn($result);

    $ai = $this->mock(AiClassifier::class);
    $ai->shouldNotReceive('classify');

    $orchestrator = app(ClassificationOrchestratorService::class);
    $orchestrator->process($document);

    $document->refresh();
    expect($document->document_type_id)->toBe($documentType->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2 — RegexClassifier=null, AiClassifier con risultato valido →
//           documento aggiornato con dati AI
// Requirements: 6.2
// ─────────────────────────────────────────────────────────────────────────────
it('usa AiClassifier quando RegexClassifier restituisce null', function () {
    $documentType = DocumentType::factory()->create(['min_confidence' => 70, 'allow_auto_verification' => false]);
    $document = Document::factory()->create(['document_type_id' => null, 'extracted_text' => 'testo di prova']);

    $result = makeResult($documentType->id, 80, 'ai');

    $regex = $this->mock(RegexClassifier::class);
    $regex->shouldReceive('classify')->once()->andReturn(null);

    $ai = $this->mock(AiClassifier::class);
    $ai->shouldReceive('classify')->once()->andReturn($result);

    $orchestrator = app(ClassificationOrchestratorService::class);
    $orchestrator->process($document);

    $document->refresh();
    expect($document->document_type_id)->toBe($documentType->id)
        ->and($document->ai_confidence_score)->toBe(80);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3 — Entrambi null → status_code='DA VERIFICARE', document_type_id=null
// Requirements: 6.3
// ─────────────────────────────────────────────────────────────────────────────
it('imposta DA VERIFICARE quando entrambi i classificatori restituiscono null', function () {
    $document = Document::factory()->create(['document_type_id' => null]);

    $regex = $this->mock(RegexClassifier::class);
    $regex->shouldReceive('classify')->once()->andReturn(null);

    $ai = $this->mock(AiClassifier::class);
    $ai->shouldReceive('classify')->once()->andReturn(null);

    $orchestrator = app(ClassificationOrchestratorService::class);
    $orchestrator->process($document);

    $document->refresh();
    expect($document->document_type_id)->toBeNull()
        ->and($document->status_code)->toBe('DA VERIFICARE');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 4 — confidenceScore >= min_confidence (ma < 95 o allow_auto_verification=false)
//           → status_code='IN VERIFICA'
// Requirements: 6.4
// ─────────────────────────────────────────────────────────────────────────────
it('imposta IN VERIFICA quando confidence >= min_confidence ma non soddisfa auto-verifica', function () {
    $documentType = DocumentType::factory()->create([
        'min_confidence'          => 70,
        'allow_auto_verification' => false,
    ]);
    $document = Document::factory()->create(['document_type_id' => null]);

    $result = makeResult($documentType->id, 80, 'ai');

    $regex = $this->mock(RegexClassifier::class);
    $regex->shouldReceive('classify')->once()->andReturn(null);

    $ai = $this->mock(AiClassifier::class);
    $ai->shouldReceive('classify')->once()->andReturn($result);

    $orchestrator = app(ClassificationOrchestratorService::class);
    $orchestrator->process($document);

    $document->refresh();
    expect($document->status_code)->toBe('IN VERIFICA');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 5 — confidenceScore >= 95 e allow_auto_verification=true
//           → status_code='OK' e verified_at valorizzato
// Requirements: 6.5
// ─────────────────────────────────────────────────────────────────────────────
it('imposta OK e verified_at quando confidence >= 95 e allow_auto_verification=true', function () {
    $documentType = DocumentType::factory()->create([
        'min_confidence'          => 70,
        'allow_auto_verification' => true,
    ]);
    $document = Document::factory()->create(['document_type_id' => null]);

    $result = makeResult($documentType->id, 96, 'ai');

    $regex = $this->mock(RegexClassifier::class);
    $regex->shouldReceive('classify')->once()->andReturn(null);

    $ai = $this->mock(AiClassifier::class);
    $ai->shouldReceive('classify')->once()->andReturn($result);

    $orchestrator = app(ClassificationOrchestratorService::class);
    $orchestrator->process($document);

    $document->refresh();
    expect($document->status_code)->toBe('OK')
        ->and($document->verified_at)->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 6 — confidenceScore < min_confidence → status_code='RICHIESTA INFO'
// Requirements: 6.6
// ─────────────────────────────────────────────────────────────────────────────
it('imposta RICHIESTA INFO quando confidence < min_confidence', function () {
    $documentType = DocumentType::factory()->create([
        'min_confidence'          => 70,
        'allow_auto_verification' => false,
    ]);
    $document = Document::factory()->create(['document_type_id' => null]);

    $result = makeResult($documentType->id, 50, 'ai');

    $regex = $this->mock(RegexClassifier::class);
    $regex->shouldReceive('classify')->once()->andReturn(null);

    $ai = $this->mock(AiClassifier::class);
    $ai->shouldReceive('classify')->once()->andReturn($result);

    $orchestrator = app(ClassificationOrchestratorService::class);
    $orchestrator->process($document);

    $document->refresh();
    expect($document->status_code)->toBe('RICHIESTA INFO');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 7 — Classificazione riuscita → viene creato un record ClassificationLog
// Requirements: 6.7
// ─────────────────────────────────────────────────────────────────────────────
it('crea un ClassificationLog per ogni classificazione riuscita', function () {
    $documentType = DocumentType::factory()->create(['min_confidence' => 70]);
    $document = Document::factory()->create(['document_type_id' => null]);

    $result = makeResult($documentType->id, 85, 'regex');

    $regex = $this->mock(RegexClassifier::class);
    $regex->shouldReceive('classify')->once()->andReturn($result);

    $this->mock(AiClassifier::class)->shouldNotReceive('classify');

    $orchestrator = app(ClassificationOrchestratorService::class);
    $orchestrator->process($document);

    expect(ClassificationLog::where('document_id', $document->id)->count())->toBe(1);

    $log = ClassificationLog::where('document_id', $document->id)->first();
    expect($log->predicted_type_id)->toBe($documentType->id)
        ->and($log->classifier_used)->toBe('regex')
        ->and($log->confidence_score)->toBe(85);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 8 — Listener RunDocumentClassification salta se document_type_id è già valorizzato
// Requirements: 6.8
// ─────────────────────────────────────────────────────────────────────────────
it('RunDocumentClassification salta elaborazione se document_type_id è già valorizzato', function () {
    $documentType = DocumentType::factory()->create();
    $document = Document::factory()->create(['document_type_id' => $documentType->id]);

    $orchestrator = $this->mock(ClassificationOrchestratorService::class);
    $orchestrator->shouldNotReceive('process');

    $event = new DocumentUploaded($document);
    $listener = app(RunDocumentClassification::class);
    $listener->handle($event);
});
