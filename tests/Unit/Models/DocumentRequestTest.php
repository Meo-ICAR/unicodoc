<?php

use App\Models\DocumentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Test 1: isPending() — true quando status=PENDING, false per gli altri
it('isPending returns true only when status is PENDING', function () {
    $pending = DocumentRequest::factory()->create(['status' => 'PENDING']);
    $partial = DocumentRequest::factory()->create(['status' => 'PARTIAL']);

    expect($pending->isPending())->toBeTrue();
    expect($pending->isPartial())->toBeFalse();
    expect($pending->isCompleted())->toBeFalse();

    expect($partial->isPending())->toBeFalse();
});

// Test 2: isCompleted() — true quando status=COMPLETED
it('isCompleted returns true when status is COMPLETED', function () {
    $completed = DocumentRequest::factory()->create(['status' => 'COMPLETED']);

    expect($completed->isCompleted())->toBeTrue();
    expect($completed->isPending())->toBeFalse();
    expect($completed->isPartial())->toBeFalse();
});

// Test 3: isExpired() — true con expires_at nel passato indipendentemente da status
it('isExpired returns true when expires_at is in the past regardless of status', function () {
    foreach (['PENDING', 'PARTIAL', 'COMPLETED', 'EXPIRED'] as $status) {
        $request = DocumentRequest::factory()->create([
            'status'     => $status,
            'expires_at' => now()->subDay(),
        ]);

        expect($request->isExpired())->toBeTrue("Failed for status: {$status}");
    }
});

// Test 4: isExpired() — false con expires_at nel futuro e status != EXPIRED
it('isExpired returns false when expires_at is in the future and status is not EXPIRED', function () {
    foreach (['PENDING', 'PARTIAL', 'COMPLETED'] as $status) {
        $request = DocumentRequest::factory()->create([
            'status'     => $status,
            'expires_at' => now()->addDays(7),
        ]);

        expect($request->isExpired())->toBeFalse("Failed for status: {$status}");
    }
});

// Test 5: getMagicLink() — restituisce stringa URL non vuota
it('getMagicLink returns a non-empty URL string', function () {
    $request = DocumentRequest::factory()->create();

    $link = $request->getMagicLink();

    expect($link)->toBeString();
    expect($link)->not->toBeEmpty();
    expect(filter_var($link, FILTER_VALIDATE_URL))->not->toBeFalse();
});

// Test 6: getFormattedStatus() — label italiana corretta per tutti e quattro gli stati
it('getFormattedStatus returns correct Italian label for each status', function () {
    $cases = [
        'PENDING'   => 'In Attesa',
        'PARTIAL'   => 'Parziale',
        'COMPLETED' => 'Completato',
        'EXPIRED'   => 'Scaduto',
    ];

    foreach ($cases as $status => $expectedLabel) {
        $request = DocumentRequest::factory()->create(['status' => $status]);

        expect($request->getFormattedStatus())->toBe($expectedLabel);
    }
});

// Test 7: scopeExpiringSoon(N) — include solo record con expires_at tra 1 e N giorni
it('scopeExpiringSoon includes only records expiring within N days', function () {
    // Scade tra 3 giorni → deve essere incluso con N=5
    $soonExpiring = DocumentRequest::factory()->create([
        'expires_at' => now()->addDays(3),
    ]);

    // Scade tra 10 giorni → non incluso con N=5
    $notSoon = DocumentRequest::factory()->create([
        'expires_at' => now()->addDays(10),
    ]);

    // Già scaduto → non incluso
    $alreadyExpired = DocumentRequest::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    $results = DocumentRequest::expiringSoon(5)->get();

    expect($results->contains($soonExpiring))->toBeTrue();
    expect($results->contains($notSoon))->toBeFalse();
    expect($results->contains($alreadyExpired))->toBeFalse();
});

// Test 8: getExpiryProgress() — valore tra 0 e 100
it('getExpiryProgress returns a value between 0 and 100', function () {
    // Creato 5 giorni fa, scade tra 5 giorni → ~50%
    $request = DocumentRequest::factory()->create([
        'created_at' => now()->subDays(5),
        'expires_at' => now()->addDays(5),
    ]);

    $progress = $request->getExpiryProgress();

    expect($progress)->toBeGreaterThanOrEqual(0.0);
    expect($progress)->toBeLessThanOrEqual(100.0);
});
