<?php

use App\Enums\DocumentStatus;

$validValues = ['uploaded', 'verified', 'rejected', 'expired', 'revoked', 'pending'];
$validColors = ['warning', 'success', 'danger', 'gray', 'info'];

it('istanzia tutti i valori via from() senza eccezioni', function () use ($validValues) {
    foreach ($validValues as $value) {
        expect(DocumentStatus::from($value))->toBeInstanceOf(DocumentStatus::class);
    }
});

it('getLabel() restituisce una stringa non vuota per ogni valore', function () use ($validValues) {
    foreach ($validValues as $value) {
        $label = DocumentStatus::from($value)->getLabel();
        expect($label)->toBeString()->not->toBeEmpty();
    }
});

it('getColor() restituisce un colore Filament valido per ogni valore', function () use ($validValues, $validColors) {
    foreach ($validValues as $value) {
        $color = DocumentStatus::from($value)->getColor();
        expect($color)->toBeIn($validColors);
    }
});

it('getIcon() restituisce un nome icona non vuoto per ogni valore', function () use ($validValues) {
    foreach ($validValues as $value) {
        $icon = DocumentStatus::from($value)->getIcon();
        expect($icon)->toBeString()->not->toBeEmpty();
    }
});

it('from() lancia ValueError per una stringa non valida', function () {
    DocumentStatus::from('stringa_invalida');
})->throws(ValueError::class);
