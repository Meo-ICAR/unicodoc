<?php

use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

$basePayload = fn (array $overrides = []) => array_merge([
    'documentable_type' => 'App\\Models\\BPM\\Client',
    'documentable_id' => (string) Str::uuid(),
    'required_codes' => [],
], $overrides);

// 1. HTTP 200/201 con request_id, upload_url ed expires_at per richiesta valida
it('returns request_id, upload_url and expires_at for a valid request', function () use ($basePayload) {
    $type = DocumentType::factory()->create(['code' => 'DOC_TEST']);

    $response = $this->postJson('/api/v1/requests', $basePayload([
        'required_codes' => ['DOC_TEST'],
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure(['request_id', 'upload_url', 'expires_at']);

    expect($response->json('request_id'))->not->toBeNull();
    expect($response->json('upload_url'))->not->toBeNull();
    expect($response->json('expires_at'))->not->toBeNull();
});

// 2. HTTP 422 quando almeno un codice in required_codes non esiste
it('returns 422 when at least one required_code does not exist', function () use ($basePayload) {
    DocumentType::factory()->create(['code' => 'DOC_EXISTS']);

    $response = $this->postJson('/api/v1/requests', $basePayload([
        'required_codes' => ['DOC_EXISTS', 'DOC_NOT_FOUND'],
    ]));

    $response->assertStatus(422);
});

// 3. expires_at a 7 giorni dalla creazione quando expires_in_days non è specificato
it('sets expires_at to 7 days from now when expires_in_days is not specified', function () use ($basePayload) {
    $type = DocumentType::factory()->create(['code' => 'DOC_EXP']);

    $response = $this->postJson('/api/v1/requests', $basePayload([
        'required_codes' => ['DOC_EXP'],
    ]));

    $response->assertStatus(200);

    $expiresAt = \Carbon\Carbon::parse($response->json('expires_at'));
    $expected = now()->addDays(7);

    expect($expiresAt->diffInSeconds($expected))->toBeLessThan(5);
});

// 4. expires_at rispetta expires_in_days quando specificato
it('sets expires_at according to expires_in_days when specified', function () use ($basePayload) {
    $type = DocumentType::factory()->create(['code' => 'DOC_DAYS']);

    $response = $this->postJson('/api/v1/requests', $basePayload([
        'required_codes' => ['DOC_DAYS'],
        'expires_in_days' => 14,
    ]));

    $response->assertStatus(200);

    $expiresAt = \Carbon\Carbon::parse($response->json('expires_at'));
    $expected = now()->addDays(14);

    expect($expiresAt->diffInSeconds($expected))->toBeLessThan(5);
});

// 5. Vengono creati esattamente N DocumentRequestItem per N codici in required_codes
it('creates exactly N DocumentRequestItems for N required_codes', function () use ($basePayload) {
    $types = DocumentType::factory()->count(3)->create();
    $codes = $types->pluck('code')->toArray();

    $response = $this->postJson('/api/v1/requests', $basePayload([
        'required_codes' => $codes,
    ]));

    $response->assertStatus(200);

    $requestId = $response->json('request_id');
    $itemCount = DocumentRequestItem::where('document_request_id', $requestId)->count();

    expect($itemCount)->toBe(3);
});

// 6. upload_url contiene l'UUID del DocumentRequest
it('upload_url contains the DocumentRequest UUID', function () use ($basePayload) {
    $type = DocumentType::factory()->create(['code' => 'DOC_URL']);

    $response = $this->postJson('/api/v1/requests', $basePayload([
        'required_codes' => ['DOC_URL'],
    ]));

    $response->assertStatus(200);

    $requestId = $response->json('request_id');
    $uploadUrl = $response->json('upload_url');

    expect($uploadUrl)->toContain($requestId);
});

// 7. HTTP 422 quando mancano documentable_type, documentable_id o required_codes
it('returns 422 when documentable_type is missing', function () {
    $this->postJson('/api/v1/requests', [
        'documentable_id' => 'some-id',
        'required_codes' => ['DOC_A'],
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['documentable_type']);
});

it('returns 422 when documentable_id is missing', function () {
    $this->postJson('/api/v1/requests', [
        'documentable_type' => 'App\\Models\\BPM\\Client',
        'required_codes' => ['DOC_A'],
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['documentable_id']);
});

it('returns 422 when required_codes is missing', function () {
    $this->postJson('/api/v1/requests', [
        'documentable_type' => 'App\\Models\\BPM\\Client',
        'documentable_id' => 'some-id',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['required_codes']);
});
