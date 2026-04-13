<?php

namespace App\Services\Mail;

use App\Models\MailAccount;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client;

class MailSyncService
{
    public function syncAccount(MailAccount $account): void
    {
        try {
            // Connessione IMAP dinamica basata sulle credenziali a DB
            $client = Client::make([
                'host' => $account->host,
                'port' => $account->port,
                'encryption' => $account->encryption,
                'validate_cert' => true,
                'username' => $account->email,
                'password' => decrypt($account->credentials),
                'protocol' => 'imap'
            ]);

            $client->connect();
            $folder = $client->getFolder('INBOX');

            // Prendiamo solo i messaggi non letti (o dall'ultimo sync)
            $messages = $folder->query()->unseen()->get();

            foreach ($messages as $message) {
                // Evita duplicati usando il Message-ID dell'header
                $mailMessage = MailMessage::firstOrCreate(
                    ['message_id' => $message->getMessageId()],
                    [
                        'mail_account_id' => $account->id,
                        'from_address' => $message->getFrom()[0]->mail,
                        'to_address' => $account->email,
                        'subject' => $message->getSubject(),
                        'body_text' => $message->getTextBody() ?? $message->getHTMLBody(),
                    ]
                );

                $this->processAttachments($message, $mailMessage);

                // Segna come letto sul server per non riscaricarlo
                $message->setFlag('Seen');
            }

            $account->update(['last_synced_at' => now()]);
        } catch (\Exception $e) {
            Log::error("Impossibile sincronizzare l'account {$account->email}: " . $e->getMessage());
        }
    }

    protected function processAttachments($imapMessage, MailMessage $mailMessage): void
    {
        foreach ($imapMessage->getAttachments() as $attachment) {
            // Salvataggio temporaneo del file nello storage (Buffer)
            $path = "mail_buffer/{$mailMessage->id}/" . $attachment->name;
            \Storage::put($path, $attachment->content);

            MailAttachment::create([
                'id' => \Str::uuid(),
                'mail_message_id' => $mailMessage->id,
                'filename' => $attachment->name,
                'mime_type' => $attachment->mime,
                'size' => $attachment->size,
                'content_id' => $attachment->id,
                'is_inline' => $attachment->disposition === 'inline',
            ]);
        }
    }
}
