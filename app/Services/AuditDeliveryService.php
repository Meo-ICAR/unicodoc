<?php
namespace App\Services;

use App\Models\AuditExport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuditDeliveryService
{
    public function prepareSecureDelivery(AuditExport $export, string $phone): void
    {
        // 1. Genera PIN casuale a 6 cifre
        $pin = (string) rand(100000, 999999);

        // 2. Salva Hash del PIN (come una password)
        $export->update([
            'access_pin' => Hash::make($pin),
            'expires_at' => now()->addDays(7),
            'status' => 'ready'
        ]);

        // 3. Invio OOB: Link via Mail, PIN via SMS
        // Mail::to($auditorEmail)->send(new AuditLinkMail($export));
        // SmsProvider::send($phone, "Il tuo PIN di sblocco per il dossier UnicoDoc è: {$pin}");
    }
}
