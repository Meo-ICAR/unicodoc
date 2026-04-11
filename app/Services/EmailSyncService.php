<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Employee;
use App\Models\MailAccount;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailSyncService
{
    /**
     * Synchronizes emails from a given MailAccount.
     * Note: This assumes a library like webklex/laravel-imap is used.
     * Here we provide the logic to save and route.
     */
    public function syncAccount(MailAccount $account)
    {
        // Example with imap_open or similar logic (conceptual for the sync flow)
        // In a real app, you'd iterate over messages from the IMAP client
        
        // $client = $this->getImapClient($account);
        // $messages = $client->getUnseenMessages();
        
        // foreach ($messages as $message) {
        //     $this->processMessage($account, $message);
        // }
        
        $account->update(['last_synced_at' => now()]);
    }

    public function processMessage(MailAccount $account, $incomingMessage)
    {
        $mailMessage = MailMessage::create([
            'mail_account_id' => $account->id,
            'message_id' => $incomingMessage->getMessageId(),
            'from_address' => $incomingMessage->getFromAddress(),
            'from_name' => $incomingMessage->getFromName(),
            'to_address' => $account->email,
            'subject' => $incomingMessage->getSubject(),
            'body_text' => $incomingMessage->getTextBody(),
            'body_html' => $incomingMessage->getHtmlBody(),
            'received_at' => $incomingMessage->getDate(),
            'metadata' => [
                'headers' => $incomingMessage->getHeaders(),
            ],
        ]);

        $this->routeMessage($mailMessage);
        
        $this->extractAttachments($mailMessage, $incomingMessage);
        
        return $mailMessage;
    }

    public function routeMessage(MailMessage $message)
    {
        // 1. Try to associate with an Employee (sender/recipient)
        $employee = Employee::where('email', $message->from_address)
            ->orWhere('pec', $message->from_address)
            ->first();

        if ($employee) {
            $message->update(['associated_employee_id' => $employee->id]);
        }

        // 2. Try to associate with a Client
        $client = Client::where('email', $message->from_address)->first();
        if ($client) {
            $message->update(['associated_client_id' => $client->id]);
        }

        // 3. AI routing logic would go here
        // If no direct match, use AI to analyze body_text and subject
    }

    protected function extractAttachments(MailMessage $mailMessage, $incomingMessage)
    {
        foreach ($incomingMessage->getAttachments() as $attachment) {
            $mailAttachment = MailAttachment::create([
                'mail_message_id' => $mailMessage->id,
                'file_name' => $attachment->getName(),
                'mime_type' => $attachment->getMimeType(),
                'size' => $attachment->getSize(),
            ]);

            // Save attachment to temp storage for classification
            $path = 'temp_attachments/' . $mailAttachment->id . '_' . $attachment->getName();
            Storage::put($path, $attachment->getContent());

            // Further logic will convert this to a Document model
        }
    }
}
