<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class RequestRegistryProcess extends Model
{
    protected $fillable = [
        'registry_id',
        'process_id',
        'process_task_id',
        'outcome',
        'completed_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'registry_id' => 'integer',
        'process_id' => 'integer',
        'process_task_id' => 'integer',
        'completed_at' => 'datetime',
    ];

    protected $dates = [
        'completed_at',
    ];

    public function registry(): BelongsTo
    {
        return $this->belongsTo(RequestRegistry::class, 'registry_id');
    }

    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    public function isPending(): bool
    {
        return is_null($this->completed_at);
    }

    public function hasTask(): bool
    {
        return !is_null($this->process_task_id);
    }

    public function getFormattedOutcome(): string
    {
        return $this->outcome ?? 'In corso';
    }

    public function getOutcomeColor(): string
    {
        if ($this->isPending()) {
            return 'warning';
        }

        return match (true) {
            str_contains(strtolower($this->outcome ?? ''), 'success') => 'success',
            str_contains(strtolower($this->outcome ?? ''), 'error') => 'danger',
            str_contains(strtolower($this->outcome ?? ''), 'warning') => 'warning',
            default => 'info',
        };
    }

    public function getDuration(): ?string
    {
        if (!$this->completed_at) {
            return null;
        }

        $duration = $this->created_at->diffForHumans($this->completed_at, true);
        return $duration;
    }

    public function scopeByRegistry($query, $registryId)
    {
        return $query->where('registry_id', $registryId);
    }

    public function scopeByProcess($query, $processId)
    {
        return $query->where('process_id', $processId);
    }

    public function scopeByTask($query, $taskId)
    {
        return $query->where('process_task_id', $taskId);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeWithTask($query)
    {
        return $query->whereNotNull('process_task_id');
    }

    public function scopeByOutcome($query, $outcome)
    {
        return $query->where('outcome', 'like', "%{$outcome}%");
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
