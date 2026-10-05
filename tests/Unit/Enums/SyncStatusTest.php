<?php

use App\Enums\SyncStatus;

$validValues = ['local', 'syncing', 'synced', 'failed'];

it('getLabel(), getColor() e getIcon() sono non nulli e non vuoti per ogni valore', function () use ($validValues) {
    foreach ($validValues as $value) {
        $status = SyncStatus::from($value);

        expect($status->getLabel())->toBeString()->not->toBeEmpty();
        expect($status->getColor())->not->toBeNull()->not->toBeEmpty();
        expect($status->getIcon())->toBeString()->not->toBeEmpty();
    }
});
