<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestRegistry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'request_number',
        'request_date',
        'received_via',
        'requester_type',
        'requester_name',
        'requester_contact',
        'mandate_reference',
        'oversight_body_type',
        'request_type',
        'data_subject_type',
        'data_subject_id',
        'description',
        'status',
        'response_deadline',
        'response_date',
        'response_summary',
        'sla_breach',
        'extension_granted',
        'extension_reason',
        'assigned_to',
        'active_process_id',
        'process_task_id',
        'bpm_context',
        'notes',
    ];

    protected $casts = [
        'id' => 'integer',
        'company_id' => 'string',
        'data_subject_id' => 'integer',
        'assigned_to' => 'integer',
        'active_process_id' => 'integer',
        'process_task_id' => 'integer',
        'sla_breach' => 'boolean',
        'extension_granted' => 'boolean',
        'request_date' => 'date',
        'response_deadline' => 'date',
        'response_date' => 'date',
        'bpm_context' => 'array',
    ];

    protected $dates = [
        'request_date',
        'response_deadline',
        'response_date',
    ];

    public function dataSubject(): MorphTo
    {
        return $this->morphTo('data_subject');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(RequestRegistryAction::class, 'registry_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RequestRegistryAttachment::class, 'registry_id');
    }

    public function processes(): HasMany
    {
        return $this->hasMany(RequestRegistryProcess::class, 'registry_id');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(RequestEmailLog::class, 'registry_id');
    }

    public function isReceived(): bool
    {
        return $this->status === 'ricevuta';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_lavorazione';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'evasa';
    }

    public function isRejected(): bool
    {
        return $this->status === 'respinta';
    }

    public function isPartiallyCompleted(): bool
    {
        return $this->status === 'parzialmente_evasa';
    }

    public function isExpired(): bool
    {
        return $this->status === 'scaduta';
    }

    public function hasSLABreach(): bool
    {
        return $this->sla_breach ||
            ($this->response_deadline && $this->response_deadline->isPast() && !$this->isCompleted());
    }

    public function getDaysUntilDeadline(): ?int
    {
        if (!$this->response_deadline) {
            return null;
        }

        return $this->response_deadline->diffInDays(now(), false);
    }

    public function getFormattedStatus(): string
    {
        return match ($this->status) {
            'ricevuta' => 'Ricevuta',
            'in_lavorazione' => 'In Lavorazione',
            'evasa' => 'Evasa',
            'respinta' => 'Respinta',
            'parzialmente_evasa' => 'Parzialmente Evasa',
            'scaduta' => 'Scaduta',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'ricevuta' => 'info',
            'in_lavorazione' => 'warning',
            'evasa' => 'success',
            'respinta' => 'danger',
            'parzialmente_evasa' => 'primary',
            'scaduta' => 'danger',
            default => 'gray',
        };
    }

    public function getFormattedRequestType(): string
    {
        return match ($this->request_type) {
            'accesso' => 'Accesso ai Dati',
            'cancellazione' => 'Cancellazione (Right to be Forgotten)',
            'rettifica' => 'Rettifica',
            'opposizione' => 'Opposizione',
            'limitazione' => 'Limitazione',
            'portabilita' => 'Portabilità',
            'revoca_consenso' => 'Revoca Consenso',
            'reclamazione' => 'Reclamazione',
            default => $this->request_type,
        };
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByRequestType($query, $type)
    {
        return $query->where('request_type', $type);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeWithSLABreach($query)
    {
        return $query
            ->where('sla_breach', true)
            ->orWhere('response_deadline', '<', now());
    }

    public function scopeExpiringSoon($query, $days = 5)
    {
        return $query
            ->where('response_deadline', '<=', now()->addDays($days))
            ->where('response_deadline', '>', now())
            ->whereNotIn('status', ['evasa', 'respinta']);
    }

    public function scopeByDataSubject($query, $type, $id = null)
    {
        $query->where('data_subject_type', $type);

        if ($id !== null) {
            $query->where('data_subject_id', $id);
        }

        return $query;
    }
}
