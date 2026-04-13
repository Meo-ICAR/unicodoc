<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail_message_id',
        'filename',
        'mime_type',
        'size',
        'is_inline',
        'content',
        'document_id',
    ];

    protected $casts = [
        'size'      => 'integer',
        'is_inline' => 'boolean',
    ];

    public function mailMessage(): BelongsTo
    {
        return $this->belongsTo(MailMessage::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
