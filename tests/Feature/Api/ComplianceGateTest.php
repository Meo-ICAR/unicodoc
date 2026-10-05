<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper: crea un documento associato a un'entità con un tipo specifico
function makeDocumentForEntity(DocumentType $type, string $entityType, string $entityId, array $overrides = []): Document
{
    return Document::factory()->create(array_merge([
        'document_type_id' => $type->id,
        'documentable_type' => $entityType,
        'documentable_id' => $entityId,
    ], $overrides));
}

// 1. HTTP 200 con is_compliant=true quando tutti i codici hanno documenti validi e non scaduti
it('returns is_compliant true when all required codes have valid non-expired documents', function () {
    $entityType = 'App\\Models\\BPM\\Client';
    $entityId = (string) \Illuminate\Support\Str::uuid();

    $type1 = DocumentType::factory()->create(['code' => 'DOC_A']);
    $type2 = DocumentType::factory()->create(['code' => 'DOC_B']);

    makeDocumentForEntity($type1, $entityType, $entityId, ['status' => DocumentStatus::VERIFIED, 'expires_at' => null]);
    makeDocumentForEntity($type2, $entityType, $entityId, ['status' => DocumentStatus::VERIFIED, 'expires_at' => null]);

    $response = $this->postJson('/api/v1/compliance/check', [
        'documentable_type' => $entityType,
        'documentable_id' => $entityId,
        'required_codes' => ['DOC_A', 'DOC_B'],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('is_compliant', true)
        ->assertJsonPath('summary.valid', 2)
        ->assertJsonPath('summary.invalid', 0)
        ->assertJsonPath('summary.missing', 0)
        ->assertJsonPath('summary.total_required', 2);
});

// 2. is_compliant=false con codice in missing_documents quando manca un documento
it('returns is_compliant false with missing code when document is absent', function () {
    $entityType = 'App\\Models\\BPM\\Client';
    $entityId = (string) \Illuminate\Support\Str::uuid();

    $type1 = DocumentType::factory()->create(['code' => 'DOC_PRESENT']);
    DocumentType::factory()->create(['code' => 'DOC_MISSING']);

    makeDocumentForEntity($type1, $entityType, $entityId, ['status' => DocumentStatus::VERIFIED]);

    $response = $this->postJson('/api/v1/compliance/check', [
        'documentable_type' => $entityType,
        'documentable_id' => $entityId,
        'required_codes' => ['DOC_PRESENT', 'DOC_MISSING'],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('is_compliant', false);

    $missing = $response->json('details.missing_documents');
    expect($missing)->toContain('DOC_MISSING');
    expect($missing)->not->toContain('DOC_PRESENT');
});

// 3. is_compliant=false con documento in invalid_documents quando lo stato non è valido o è scaduto
it('returns is_compliant false with invalid document when status is not verified', function () {
    $entityType = 'App\\Models\\BPM\\Client';
    $entityId = (string) \Illuminate\Support\Str::uuid();

    $type = DocumentType::factory()->create(['code' => 'DOC_INVALID']);

    makeDocumentForEntity($type, $entityType, $entityId, ['status' => DocumentStatus::REJECTED]);

    $response = $this->postJson('/api/v1/compliance/check', [
        'documentable_type' => $entityType,
        'documentable_id' => $entityId,
        'required_codes' => ['DOC_INVALID'],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('is_compliant', false);

    $invalid = $response->json('details.invalid_documents');
    expect($invalid)->toHaveCount(1);
    expect($invalid[0]['code'])->toBe('DOC_INVALID');
});

it('returns is_compliant false with invalid document when document is expired', function () {
    $entityType = 'App\\Models\\BPM\\Client';
    $entityId = (string) \Illuminate\Support\Str::uuid();

    $type = DocumentType::factory()->create(['code' => 'DOC_EXPIRED']);

    makeDocumentForEntity($type, $entityType, $entityId, [
        'status' => DocumentStatus::VERIFIED,
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->postJson('/api/v1/compliance/check', [
        'documentable_type' => $entityType,
        'documentable_id' => $entityId,
        'required_codes' => ['DOC_EXPIRED'],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('is_compliant', false);

    $invalid = $response->json('details.invalid_documents');
    expect($invalid)->toHaveCount(1);
    expect($invalid[0]['code'])->toBe('DOC_EXPIRED');
});

// 4. HTTP 422 quando mancano documentable_type, documentable_id o required_codes
it('returns 422 when documentable_type is missing', function () {
    $this->postJson('/api/v1/compliance/check', [
        'documentable_id' => 'some-id',
        'required_codes' => ['DOC_A'],
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['documentable_type']);
});

it('returns 422 when documentable_id is missing', function () {
    $this->postJson('/api/v1/compliance/check', [
        'documentable_type' => 'App\\Models\\BPM\\Client',
        'required_codes' => ['DOC_A'],
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['documentable_id']);
});

it('returns 422 when required_codes is missing', function () {
    $this->postJson('/api/v1/compliance/check', [
        'documentable_type' => 'App\\Models\\BPM\\Client',
        'documentable_id' => 'some-id',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['required_codes']);
});

// 5. HTTP 422 quando required_codes è un array vuoto
it('returns 422 when required_codes is an empty array', function () {
    $this->postJson('/api/v1/compliance/check', [
        'documentable_type' => 'App\\Models\\BPM\\Client',
        'documentable_id' => 'some-id',
        'required_codes' => [],
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['required_codes']);
});

// 6. Verifica coerenza del campo summary: valid + invalid + missing == total_required
it('summary counts are always consistent: valid + invalid + missing == total_required', function () {
    $entityType = 'App\\Models\\BPM\\Client';
    $entityId = (string) \Illuminate\Support\Str::uuid();

    $typeValid = DocumentType::factory()->create(['code' => 'DOC_V']);
    $typeInvalid = DocumentType::factory()->create(['code' => 'DOC_I']);
    DocumentType::factory()->create(['code' => 'DOC_M']); // missing — no document created

    makeDocumentForEntity($typeValid, $entityType, $entityId, ['status' => DocumentStatus::VERIFIED, 'expires_at' => null]);
    makeDocumentForEntity($typeInvalid, $entityType, $entityId, ['status' => DocumentStatus::UPLOADED]);

    $response = $this->postJson('/api/v1/compliance/check', [
        'documentable_type' => $entityType,
        'documentable_id' => $entityId,
        'required_codes' => ['DOC_V', 'DOC_I', 'DOC_M'],
    ]);

    $response->assertStatus(200);

    $summary = $response->json('summary');
    expect($summary['valid'] + $summary['invalid'] + $summary['missing'])
        ->toBe($summary['total_required']);
    expect($summary['total_required'])->toBe(3);
    expect($summary['valid'])->toBe(1);
    expect($summary['invalid'])->toBe(1);
    expect($summary['missing'])->toBe(1);
});
