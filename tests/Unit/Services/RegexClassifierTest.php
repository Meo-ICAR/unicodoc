<?php

use App\Models\Document;
use App\Models\DocumentType;
use App\Services\Classification\RegexClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Svuota la cache dei regex_pattern tra un test e l'altro
    Cache::forget('classification_regex_rules');
});

it('restituisce ClassificationResult con confidenceScore=100 e classifierUsed=regex quando il pattern corrisponde', function () {
    $type = DocumentType::factory()->create([
        'regex_pattern' => 'fattura',
        'priority'      => 1,
    ]);

    $document = Document::factory()->create([
        'extracted_text' => 'Questa è una fattura numero 123',
        'document_type_id' => $type->id,
    ]);

    $result = (new RegexClassifier())->classify($document);

    expect($result)->not->toBeNull()
        ->and($result->confidenceScore)->toBe(100)
        ->and($result->classifierUsed)->toBe('regex')
        ->and($result->documentTypeId)->toBe($type->id);
});

it('restituisce null quando nessun pattern corrisponde al testo', function () {
    DocumentType::factory()->create([
        'regex_pattern' => 'contratto',
        'priority'      => 1,
    ]);

    $document = Document::factory()->create([
        'extracted_text' => 'Questo è un documento generico senza corrispondenza',
    ]);

    $result = (new RegexClassifier())->classify($document);

    expect($result)->toBeNull();
});

it('restituisce null quando il documento non ha extracted_text né name', function () {
    DocumentType::factory()->create([
        'regex_pattern' => 'fattura',
        'priority'      => 1,
    ]);

    $document = Document::factory()->make([
        'extracted_text' => null,
        'name'           => null,
    ]);

    $result = (new RegexClassifier())->classify($document);

    expect($result)->toBeNull();
});

it('restituisce il DocumentType con priority più alta quando più pattern corrispondono', function () {
    $lowPriority = DocumentType::factory()->create([
        'regex_pattern' => 'documento',
        'priority'      => 1,
    ]);

    $highPriority = DocumentType::factory()->create([
        'regex_pattern' => 'documento',
        'priority'      => 10,
    ]);

    $document = Document::factory()->create([
        'extracted_text' => 'Questo è un documento importante',
    ]);

    $result = (new RegexClassifier())->classify($document);

    expect($result)->not->toBeNull()
        ->and($result->documentTypeId)->toBe($highPriority->id);
});

it('esegue il match in modalità case-insensitive', function () {
    $type = DocumentType::factory()->create([
        'regex_pattern' => 'FATTURA',
        'priority'      => 1,
    ]);

    $document = Document::factory()->create([
        'extracted_text' => 'Questa è una fattura numero 456',
    ]);

    $result = (new RegexClassifier())->classify($document);

    expect($result)->not->toBeNull()
        ->and($result->documentTypeId)->toBe($type->id);
});

it('include la stringa corrispondente in evidence[matched_string]', function () {
    DocumentType::factory()->create([
        'regex_pattern' => 'fattura\s+n\.\s*\d+',
        'priority'      => 1,
    ]);

    $document = Document::factory()->create([
        'extracted_text' => 'Emessa fattura n. 789 del 01/01/2024',
    ]);

    $result = (new RegexClassifier())->classify($document);

    expect($result)->not->toBeNull()
        ->and($result->evidence)->toHaveKey('matched_string')
        ->and($result->evidence['matched_string'])->toBeString()
        ->and(strlen($result->evidence['matched_string']))->toBeGreaterThan(0);
});
