<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class RequestEmailLog extends Model
{
    protected $fillable = [
        'registry_id',
        'recipient',
        'subject',
        'body',
        'sent_by',
    ];

    protected $casts = [
        'id' => 'integer',
        'registry_id' => 'integer',
        'sent_by' => 'integer',
    ];

    public function registry(): BelongsTo
    {
        return $this->belongsTo(RequestRegistry::class, 'registry_id');
    }

    public function getShortSubject(): string
    {
        return strlen($this->subject) > 50 ? substr($this->subject, 0, 47) . '...' : $this->subject;
    }

    public function getShortBody(): string
    {
        $plainText = strip_tags($this->body);
        return strlen($plainText) > 100 ? substr($plainText, 0, 97) . '...' : $plainText;
    }

    public function getBodyPreview(int $length = 150): string
    {
        $plainText = strip_tags($this->body);
        return strlen($plainText) > $length ? substr($plainText, 0, $length - 3) . '...' : $plainText;
    }

    public function isToMultipleRecipients(): bool
    {
        return str_contains($this->recipient, ',') || str_contains($this->recipient, ';');
    }

    public function getRecipients(): array
    {
        $recipients = preg_split('/[,;]/', $this->recipient);
        return array_map('trim', array_filter($recipients));
    }

    public function getRecipientCount(): int
    {
        return count($this->getRecipients());
    }

    public function getFormattedRecipient(): string
    {
        if ($this->isToMultipleRecipients()) {
            $count = $this->getRecipientCount();
            return $count . ' destinatari';
        }

        return $this->recipient;
    }

    public function scopeByRegistry($query, $registryId)
    {
        return $query->where('registry_id', $registryId);
    }

    public function scopeByRecipient($query, $recipient)
    {
        return $query->where('recipient', 'like', "%{$recipient}%");
    }

    public function scopeBySubject($query, $subject)
    {
        return $query->where('subject', 'like', "%{$subject}%");
    }

    public function scopeBySender($query, $senderId)
    {
        return $query->where('sent_by', $senderId);
    }

    public function scopeSentBy($query, $userId)
    {
        return $query->where('sent_by', $userId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeWithBodyContaining($query, $keyword)
    {
        return $query->where('body', 'like', "%{$keyword}%");
    }
}
