<?php
namespace App\Services;

use App\Mail\DynamicAiMail;
use App\Models\MailAccount;
use App\Models\RequestEmailLog;
use App\Models\RequestRegistry;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SmartCommunicationService
{
    public function generateAiDraft(RequestRegistry $registry, string $reason): string
    {
        // Chiamata LLM per testo empatico basato su bpm_context
        $context = $registry->bpm_context;

        return "Gentile {$registry->requester_name}, in merito alla pratica {$context['process_name']}...";
    }

    public function sendAndLog(RequestRegistry $registry, string $subject, string $body): void
    {
        // 1. Recupera l'account SMTP corretto per l'azienda della pratica
        $mailAccount = MailAccount::where('company_id', $registry->company_id)
            ->where('is_active', true)
            ->firstOrFail();

        // 2. Sovrascrivi dinamicamente la configurazione mail a runtime
        Config::set('mail.mailers.dynamic_smtp', [
            'transport' => $mailAccount->driver,
            'host' => $mailAccount->host,
            'port' => $mailAccount->port,
            'encryption' => $mailAccount->encryption,
            'username' => $mailAccount->username,
            'password' => $mailAccount->password,  // Viene decriptata in automatico dal modello
        ]);

        Config::set('mail.from.address', $mailAccount->from_address);
        Config::set('mail.from.name', $mailAccount->from_name);

        // 3. Invio fisico (forzando l'uso del mailer dinamico appena creato)
        Mail::mailer('dynamic_smtp')
            ->to($registry->requester_contact)
            ->send(new DynamicAiMail($subject, $body));

        // 4. Log Immutabile
        RequestEmailLog::create([
            'registry_id' => $registry->id,
            'recipient' => $registry->requester_contact,
            'subject' => $subject,
            'body' => $body,
            'sent_by' => auth()->id(),
        ]);
    }
}
