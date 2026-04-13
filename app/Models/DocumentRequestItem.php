<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DocumentRequestItem extends Model
{
    protected $fillable = [
        'document_request_id',
        'document_type_id',
        'fulfilled_by_document_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'document_request_id' => 'string',
        'document_type_id' => 'integer',
        'fulfilled_by_document_id' => 'string',
    ];

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function fulfilledByDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'fulfilled_by_document_id');
    }

    public function isFulfilled(): bool
    {
        return !is_null($this->fulfilled_by_document_id);
    }

    public function isPending(): bool
    {
        return is_null($this->fulfilled_by_document_id);
    }

    public function getFormattedStatus(): string
    {
        return $this->isFulfilled() ? 'Caricato' : 'In Attesa';
    }

    public function getStatusColor(): string
    {
        return $this->isFulfilled() ? 'success' : 'warning';
    }

    public function scopeFulfilled($query)
    {
        return $query->whereNotNull('fulfilled_by_document_id');
    }

    public function scopePending($query)
    {
        return $query->whereNull('fulfilled_by_document_id');
    }

    public function scopeByDocumentRequest($query, $requestId)
    {
        return $query->where('document_request_id', $requestId);
    }

    public function scopeByDocumentType($query, $typeId)
    {
        return $query->where('document_type_id', $typeId);
    }
}
