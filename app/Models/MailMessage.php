<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailMessage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'mail_account_id',
        'message_id',
        'from_address',
        'from_name',
        'to_address',
        'subject',
        'body_text',
        'body_html',
        'received_at',
        'is_processed',
        'associated_employee_id',
        'associated_client_id',
        'metadata',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'is_processed' => 'boolean',
        'metadata' => 'json',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MailAccount::class, 'mail_account_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MailAttachment::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'associated_employee_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'associated_client_id');
    }
}
