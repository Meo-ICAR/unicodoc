<?php

use App\Models\MailAccount;
use App\Services\Mail\MailAttachmentProcessor;
use App\Services\Mail\MailSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    // 1. Sincronizza le caselle attive
    $accounts = MailAccount::where('is_active', true)->get();
    $syncer = app(MailSyncService::class);

    foreach ($accounts as $account) {
        $syncer->syncAccount($account);
    }

    // 2. Processa il buffer e lancia la classificazione
    app(MailAttachmentProcessor::class)->processBuffer();
})->everyFiveMinutes()->name('mail-ingestion-pipeline')->withoutOverlapping();
