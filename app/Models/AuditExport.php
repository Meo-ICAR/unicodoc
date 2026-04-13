<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditExport extends Model
{
    protected $fillable = [
        'user_id',
        'target_organism',
        'included_ids',
        'zip_file_path',
        'status',
        'access_pin',
        'expires_at',
        'downloaded_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'included_ids' => 'array',
        'expires_at' => 'datetime',
        'downloaded_at' => 'datetime',
    ];

    protected $dates = [
        'expires_at',
        'downloaded_at',
    ];

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isDownloaded(): bool
    {
        return !is_null($this->downloaded_at);
    }

    public function canBeDownloaded(): bool
    {
        return $this->isReady() && !$this->isExpired() && !$this->isDownloaded();
    }

    public function getFormattedStatus(): string
    {
        return match($this->status) {
            'processing' => 'In Elaborazione',
            'ready' => 'Pronto',
            'failed' => 'Fallito',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'processing' => 'warning',
            'ready' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    public function getIncludedCount(): int
    {
        return count($this->included_ids ?? []);
    }

    public function getIncludedIdsList(): string
    {
        $ids = $this->included_ids ?? [];

        if (empty($ids)) {
            return 'Nessuno';
        }

        if (count($ids) <= 5) {
            return implode(', ', $ids);
        }

        return implode(', ', array_slice($ids, 0, 5)) . ' ... (+' . (count($ids) - 5) ' altri)';
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

        $totalHours = $this->created_at->diffInHours($this->expires_at);
        $elapsedHours = $this->created_at->diffInHours(now());

        return $totalHours > 0 ? min(($elapsedHours / $totalHours) * 100, 100) : 0;
    }

    public function getDownloadUrl(): ?string
    {
        if (!$this->canBeDownloaded()) {
            return null;
        }

        return route('audit-exports.download', [
            'export' => $this->id,
            'pin' => $this->access_pin,
        ]);
    }

    public function getFileName(): string
    {
        return basename($this->zip_file_path);
    }

    public function getFileSize(): ?int
    {
        if (!file_exists($this->zip_file_path)) {
            return null;
        }

        return filesize($this->zip_file_path);
    }

    public function getFormattedFileSize(): string
    {
        $size = $this->getFileSize();

        if ($size === null) {
            return 'N/D';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByOrganism($query, $organism)
    {
        return $query->where('target_organism', 'like', "%{$organism}%");
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>=', now());
        });
    }

    public function scopeDownloaded($query)
    {
        return $query->whereNotNull('downloaded_at');
    }

    public function scopeNotDownloaded($query)
    {
        return $query->whereNull('downloaded_at');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'ready')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                    })
                    ->whereNull('downloaded_at');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
