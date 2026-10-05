<?php

use App\Models\DocumentType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Test 1: canAutoVerify() — true quando allow_auto_verification=true e confidence > 95
it('canAutoVerify returns true when allow_auto_verification is true and confidence > 95', function () {
    $type = DocumentType::factory()->create([
        'allow_auto_verification' => true,
        'min_confidence'          => 70,
    ]);

    expect($type->canAutoVerify(96))->toBeTrue();
    expect($type->canAutoVerify(100))->toBeTrue();
});

// Test 2a: canAutoVerify() — false quando allow_auto_verification=false
it('canAutoVerify returns false when allow_auto_verification is false', function () {
    $type = DocumentType::factory()->create([
        'allow_auto_verification' => false,
    ]);

    expect($type->canAutoVerify(100))->toBeFalse();
});

// Test 2b: canAutoVerify() — false quando confidence <= 95
it('canAutoVerify returns false when confidence is 95 or less', function () {
    $type = DocumentType::factory()->create([
        'allow_auto_verification' => true,
    ]);

    expect($type->canAutoVerify(95))->toBeFalse();
    expect($type->canAutoVerify(80))->toBeFalse();
    expect($type->canAutoVerify(0))->toBeFalse();
});

// Test 3: meetsConfidenceThreshold() — true se confidence >= min_confidence
it('meetsConfidenceThreshold returns true when confidence meets or exceeds min_confidence', function () {
    $type = DocumentType::factory()->create(['min_confidence' => 70]);

    expect($type->meetsConfidenceThreshold(70))->toBeTrue();
    expect($type->meetsConfidenceThreshold(100))->toBeTrue();
    expect($type->meetsConfidenceThreshold(69))->toBeFalse();
});

// Test 4: hasRetentionPolicy() — false quando retention_years=null
it('hasRetentionPolicy returns false when retention_years is null', function () {
    $type = DocumentType::factory()->create(['retention_years' => null]);

    expect($type->hasRetentionPolicy())->toBeFalse();
});

// Test 5: getRetentionDate() — null quando retention_years=null
it('getRetentionDate returns null when retention_years is null', function () {
    $type = DocumentType::factory()->create(['retention_years' => null]);

    expect($type->getRetentionDate(Carbon::now()))->toBeNull();
});

// Test 6: getRetentionDate() — data corretta quando retention_years=N
it('getRetentionDate returns correct date when retention_years is set', function () {
    $type = DocumentType::factory()->create(['retention_years' => 5]);
    $documentDate = Carbon::create(2020, 1, 1);

    $retentionDate = $type->getRetentionDate($documentDate);

    expect($retentionDate)->not->toBeNull();
    expect($retentionDate->year)->toBe(2025);
    expect($retentionDate->month)->toBe(1);
    expect($retentionDate->day)->toBe(1);
});

// Test 7: isExpired() — true quando la data di ritenzione è nel passato
it('isExpired returns true when retention date is in the past', function () {
    $type = DocumentType::factory()->create(['retention_years' => 1]);
    // Documento di 2 anni fa → ritenzione scaduta 1 anno fa
    $documentDate = Carbon::now()->subYears(2);

    expect($type->isExpired($documentDate))->toBeTrue();
});

// Test 8: isExpired() — false quando la data di ritenzione è nel futuro
it('isExpired returns false when retention date is in the future', function () {
    $type = DocumentType::factory()->create(['retention_years' => 10]);
    // Documento di 1 anno fa → ritenzione tra 9 anni
    $documentDate = Carbon::now()->subYear();

    expect($type->isExpired($documentDate))->toBeFalse();
});

// Test 9: shouldNotify() — con notify_days_before=[30,7] e 10 giorni alla scadenza, restituisce [7]
it('shouldNotify returns only the active notification days', function () {
    $type = DocumentType::factory()->create([
        'retention_years'    => 1,
        'notify_days_before' => [30, 7],
    ]);

    // Documento con scadenza tra esattamente 10 giorni
    // retention_years=1, quindi documentDate deve essere 1 anno - 10 giorni fa
    $documentDate = Carbon::now()->subYear()->addDays(10);

    $result = $type->shouldNotify($documentDate);

    // Mancano 10 giorni → attivi i giorni <= 10 → [7] (30 > 10, non attivo)
    expect($result)->toBe([7]);
});
