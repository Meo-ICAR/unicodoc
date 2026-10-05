<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'documentable_type',
        'documentable_id',
        'sender_email',
        'bpm_process_id',
        'bpm_task_id',
        'status',
        'expires_at',
        'has_unread_messages',
        'last_message_received',
    ];

    protected $casts = [
        'id' => 'string',
        'documentable_id' => 'string',
        'expires_at' => 'datetime',
        'has_unread_messages' => 'boolean',
    ];

    protected $dates = [
        'expires_at',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'document_request_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\DocumentRequestItem::class, 'document_request_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isPartial(): bool
    {
        return $this->status === 'PARTIAL';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }

    public function isExpired(): bool
    {
        return $this->status === 'EXPIRED' ||
            ($this->expires_at && $this->expires_at->isPast());
    }

    public function getMagicLink(): string
    {
        return route('document-requests.show', $this->id);
    }

    public function getFormattedStatus(): string
    {
        return match ($this->status) {
            'PENDING' => 'In Attesa',
            'PARTIAL' => 'Parziale',
            'COMPLETED' => 'Completato',
            'EXPIRED' => 'Scaduto',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'PENDING' => 'warning',
            'PARTIAL' => 'info',
            'COMPLETED' => 'success',
            'EXPIRED' => 'danger',
            default => 'gray',
        };
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return $this->expires_at->diffInDays(now(), false);
    }

    public function getExpiryProgress(): float
    {
        if (!$this->expires_at) {
            return 0;
        }

        $totalDays = $this->created_at->diffInDays($this->expires_at);
        $elapsedDays = $this->created_at->diffInDays(now());

        return $totalDays > 0 ? min(($elapsedDays / $totalDays) * 100, 100) : 0;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'PARTIAL');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q
                ->where('status', 'EXPIRED')
                ->orWhere('expires_at', '<', now());
        });
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q
                ->where('status', '!=', 'EXPIRED')
                ->where(function ($subQuery) {
                    $subQuery
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', now());
                });
        });
    }

    public function scopeByBpmProcess($query, $processId)
    {
        return $query->where('bpm_process_id', $processId);
    }

    public function scopeByBpmTask($query, $taskId)
    {
        return $query->where('bpm_task_id', $taskId);
    }

    public function scopeByDocumentable($query, $model, $id = null)
    {
        $query->where('documentable_type', $model);

        if ($id !== null) {
            $query->where('documentable_id', $id);
        }

        return $query;
    }

    public function scopeExpiringSoon($query, $days = 3)
    {
        return $query
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now());
    }
}
