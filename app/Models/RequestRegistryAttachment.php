<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class RequestRegistryAttachment extends Model
{
    protected $fillable = [
        'registry_id',
        'file_path',
        'storage_disk',
        'file_type',
        'ai_validation_status',
        'ai_confidence_score',
        'uploaded_by',
    ];

    protected $casts = [
        'id' => 'integer',
        'registry_id' => 'integer',
        'uploaded_by' => 'integer',
        'ai_confidence_score' => 'decimal:2',
    ];

    public function registry(): BelongsTo
    {
        return $this->belongsTo(RequestRegistry::class, 'registry_id');
    }

    public function isRequestFile(): bool
    {
        return $this->file_type === 'richiesta';
    }

    public function isIdentityDocument(): bool
    {
        return $this->file_type === 'documento_identita';
    }

    public function isMandateDocument(): bool
    {
        return $this->file_type === 'procura_mandato';
    }

    public function isResponseDocument(): bool
    {
        return $this->file_type === 'risposta';
    }

    public function isInternalDocument(): bool
    {
        return $this->file_type === 'documentazione_interna';
    }

    public function isAIApproved(): bool
    {
        return $this->ai_validation_status === 'approved';
    }

    public function isAIRejected(): bool
    {
        return $this->ai_validation_status === 'rejected';
    }

    public function isAIPending(): bool
    {
        return $this->ai_validation_status === 'pending';
    }

    public function requiresManualReview(): bool
    {
        return $this->ai_validation_status === 'manual_review';
    }

    public function getFormattedFileType(): string
    {
        return match ($this->file_type) {
            'richiesta' => 'Richiesta',
            'documento_identita' => 'Documento di Identità',
            'procura_mandato' => 'Procura/Mandato',
            'risposta' => 'Risposta',
            'documentazione_interna' => 'Documentazione Interna',
            default => $this->file_type,
        };
    }

    public function getFormattedAIStatus(): string
    {
        return match ($this->ai_validation_status) {
            'pending' => 'In Attesa',
            'approved' => 'Approvato',
            'rejected' => 'Respinto',
            'manual_review' => 'Revisione Manuale',
            default => $this->ai_validation_status,
        };
    }

    public function getAIStatusColor(): string
    {
        return match ($this->ai_validation_status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'manual_review' => 'info',
            default => 'gray',
        };
    }

    public function getFileName(): string
    {
        return basename($this->file_path);
    }

    public function getFileExtension(): string
    {
        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    public function scopeByRegistry($query, $registryId)
    {
        return $query->where('registry_id', $registryId);
    }

    public function scopeByFileType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    public function scopeByAIStatus($query, $status)
    {
        return $query->where('ai_validation_status', $status);
    }

    public function scopeAIApproved($query)
    {
        return $query->where('ai_validation_status', 'approved');
    }

    public function scopeAIRejected($query)
    {
        return $query->where('ai_validation_status', 'rejected');
    }

    public function scopeAIPending($query)
    {
        return $query->where('ai_validation_status', 'pending');
    }

    public function scopeRequiresManualReview($query)
    {
        return $query->where('ai_validation_status', 'manual_review');
    }

    public function scopeByUploader($query, $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    public function scopeByStorageDisk($query, $disk)
    {
        return $query->where('storage_disk', $disk);
    }
}
