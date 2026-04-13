<?php

use App\Models\MailAttachment;
use App\Services\Mail\MailAttachmentProcessor;

// Sottoclasse anonima che espone shouldSkip() come public per i test
function makeProcessor(): MailAttachmentProcessor
{
    return new class extends MailAttachmentProcessor {
        public function shouldSkip(MailAttachment $attachment): bool
        {
            return parent::shouldSkip($attachment);
        }
    };
}

it('shouldSkip restituisce true per allegato con size < 8192', function () {
    $attachment = new MailAttachment([
        'size'      => 1024,
        'mime_type' => 'application/pdf',
        'is_inline' => false,
    ]);

    expect(makeProcessor()->shouldSkip($attachment))->toBeTrue();
});

it('shouldSkip restituisce true per allegato con mime_type image/gif', function () {
    $attachment = new MailAttachment([
        'size'      => 10240,
        'mime_type' => 'image/gif',
        'is_inline' => false,
    ]);

    expect(makeProcessor()->shouldSkip($attachment))->toBeTrue();
});

it('shouldSkip restituisce true per allegato inline con mime_type che inizia con image/', function () {
    $attachment = new MailAttachment([
        'size'      => 10240,
        'mime_type' => 'image/png',
        'is_inline' => true,
    ]);

    expect(makeProcessor()->shouldSkip($attachment))->toBeTrue();
});

it('shouldSkip restituisce false per allegato valido', function () {
    $attachment = new MailAttachment([
        'size'      => 10240,
        'mime_type' => 'application/pdf',
        'is_inline' => false,
    ]);

    expect(makeProcessor()->shouldSkip($attachment))->toBeFalse();
});
