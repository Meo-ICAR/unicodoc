<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailAttachment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'mail_message_id',
        'file_name',
        'mime_type',
        'size',
        'document_id',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(MailMessage::class, 'mail_message_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
