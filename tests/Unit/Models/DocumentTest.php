<?php

use App\Enums\DocumentStatus;
use App\Enums\SyncStatus;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Test 1: isExpired() — true con expires_at nel passato
it('isExpired returns true when expires_at is in the past', function () {
    $doc = Document::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    expect($doc->isExpired())->toBeTrue();
});

// Test 2: isExpired() — false con expires_at=null
it('isExpired returns false when expires_at is null', function () {
    $doc = Document::factory()->create([
        'expires_at' => null,
    ]);

    expect($doc->isExpired())->toBeFalse();
});

// Test 3: isNearExpiry(N) — true quando expires_at è entro N giorni
it('isNearExpiry returns true when expires_at is within N days', function () {
    $doc = Document::factory()->create([
        'expires_at' => now()->addDays(5),
    ]);

    expect($doc->isNearExpiry(10))->toBeTrue();
    expect($doc->isNearExpiry(5))->toBeTrue();
});

// Test 4: canBeVerified() — true quando status=UPLOADED
it('canBeVerified returns true when status is UPLOADED', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::UPLOADED,
    ]);

    expect($doc->canBeVerified())->toBeTrue();
});

// Test 5: canBeVerified() — false quando status=VERIFIED
it('canBeVerified returns false when status is VERIFIED', function () {
    $doc = Document::factory()->create([
        'status'      => DocumentStatus::VERIFIED,
        'verified_at' => now(),
    ]);

    expect($doc->canBeVerified())->toBeFalse();
});

// Test 6: canBeRejected() — true quando status=UPLOADED
it('canBeRejected returns true when status is UPLOADED', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::UPLOADED,
    ]);

    expect($doc->canBeRejected())->toBeTrue();
});

// Test 7: il campo status è castato a DocumentStatus
it('status field is cast to DocumentStatus enum', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::UPLOADED,
    ]);

    $fresh = $doc->fresh();

    expect($fresh->status)->toBeInstanceOf(DocumentStatus::class);
    expect($fresh->status)->toBe(DocumentStatus::UPLOADED);
});

// Test 8: il campo sync_status è castato a SyncStatus
it('sync_status field is cast to SyncStatus enum', function () {
    $doc = Document::factory()->create([
        'sync_status' => SyncStatus::LOCAL,
    ]);

    $fresh = $doc->fresh();

    expect($fresh->sync_status)->toBeInstanceOf(SyncStatus::class);
    expect($fresh->sync_status)->toBe(SyncStatus::LOCAL);
});

// Test 9: il campo metadata è castato ad array PHP
it('metadata field is cast to PHP array', function () {
    $doc = Document::factory()->create([
        'metadata' => ['key' => 'value', 'number' => 42],
    ]);

    $fresh = $doc->fresh();

    expect($fresh->metadata)->toBeArray();
    expect($fresh->metadata['key'])->toBe('value');
    expect($fresh->metadata['number'])->toBe(42);
});
