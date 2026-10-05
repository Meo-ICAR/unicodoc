<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class RequestRegistryAction extends Model
{
    protected $fillable = [
        'registry_id',
        'action_date',
        'action_type',
        'description',
        'payload',
        'performed_by',
    ];

    protected $casts = [
        'id' => 'integer',
        'registry_id' => 'integer',
        'performed_by' => 'integer',
        'action_date' => 'datetime',
        'payload' => 'array',
    ];

    protected $dates = [
        'action_date',
    ];

    public function registry(): BelongsTo
    {
        return $this->belongsTo(RequestRegistry::class, 'registry_id');
    }

    public function isAssignment(): bool
    {
        return $this->action_type === 'assegnazione';
    }

    public function isForward(): bool
    {
        return $this->action_type === 'inoltro';
    }

    public function isPreliminaryResponse(): bool
    {
        return $this->action_type === 'risposta_preliminare';
    }

    public function isCompletion(): bool
    {
        return $this->action_type === 'evasione';
    }

    public function isExtension(): bool
    {
        return $this->action_type === 'estensione_termini';
    }

    public function isInternalComplaint(): bool
    {
        return $this->action_type === 'reclamo_interno';
    }

    public function isAIValidation(): bool
    {
        return $this->action_type === 'ai_validation';
    }

    public function isEmailSent(): bool
    {
        return $this->action_type === 'email_inviata';
    }

    public function isDocumentRejected(): bool
    {
        return $this->action_type === 'documento_rifiutato';
    }

    public function getFormattedActionType(): string
    {
        return match ($this->action_type) {
            'assegnazione' => 'Assegnazione',
            'inoltro' => 'Inoltro',
            'risposta_preliminare' => 'Risposta Preliminare',
            'evasione' => 'Evasione',
            'estensione_termini' => 'Estensione Termini',
            'reclamo_interno' => 'Reclamo Interno',
            'ai_validation' => 'Validazione AI',
            'email_inviata' => 'Email Inviata',
            'documento_rifiutato' => 'Documento Rifiutato',
            default => $this->action_type,
        };
    }

    public function getActionColor(): string
    {
        return match ($this->action_type) {
            'assegnazione' => 'info',
            'inoltro' => 'primary',
            'risposta_preliminare' => 'warning',
            'evasione' => 'success',
            'estensione_termini' => 'warning',
            'reclamo_interno' => 'danger',
            'ai_validation' => 'secondary',
            'email_inviata' => 'info',
            'documento_rifiutato' => 'danger',
            default => 'gray',
        };
    }

    public function getAIScore(): ?float
    {
        return $this->payload['ai_score'] ?? null;
    }

    public function getEmailId(): ?string
    {
        return $this->payload['email_id'] ?? null;
    }

    public function getAnnotationCoordinates(): ?array
    {
        return $this->payload['annotations'] ?? null;
    }

    public function scopeByRegistry($query, $registryId)
    {
        return $query->where('registry_id', $registryId);
    }

    public function scopeByActionType($query, $type)
    {
        return $query->where('action_type', $type);
    }

    public function scopeByPerformer($query, $userId)
    {
        return $query->where('performed_by', $userId);
    }

    public function scopeAIActions($query)
    {
        return $query->where('action_type', 'ai_validation');
    }

    public function scopeHumanActions($query)
    {
        return $query->where('action_type', '!=', 'ai_validation');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('action_date', '>=', now()->subDays($days));
    }

    public function scopeChronological($query)
    {
        return $query->orderBy('action_date', 'asc');
    }
}
