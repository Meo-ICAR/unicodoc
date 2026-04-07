<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wildside\Userstamps\HasUserstamps;

class Document extends Model implements HasMedia
{
    use SoftDeletes, HasUserstamps, InteractsWithMedia;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'company_id',
        'documentable_type',
        'documentable_id',
        'document_type_id',
        'name',
        'docnumber',
        'spatie_collection',
        'status',
        'sync_status',
        'sharepoint_id',
        'sharepoint_drive_id',
        'sharepoint_etag',
        'extracted_text',
        'metadata',
        'ai_abstract',
        'ai_confidence_score',
        'is_template',
        'is_signed',
        'is_unique',
        'is_endMonth',
        'emitted_by',
        'emitted_at',
        'expires_at',
        'delivered_at',
        'signed_at',
        'description',
        'internal_notes',
        'rejection_note',
        'user_id',
        'uploaded_by',
        'verified_by',
        'verified_at',
        'file_hash',
    ];

    protected $casts = [
        'metadata' => 'array',
        'ai_confidence_score' => 'integer',
        'is_template' => 'boolean',
        'is_signed' => 'boolean',
        'is_unique' => 'boolean',
        'is_endMonth' => 'boolean',
        'emitted_at' => 'date',
        'expires_at' => 'date',
        'delivered_at' => 'datetime',
        'signed_at' => 'datetime',
        'verified_at' => 'datetime',
        'status' => DocumentStatus::class,
        'sync_status' => SyncStatus::class,
    ];

    protected $dates = [
        'emitted_at',
        'expires_at',
        'delivered_at',
        'signed_at',
        'verified_at',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection($this->spatie_collection ?? 'default')
            ->singleFile();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isNearExpiry(int $days = 30): bool
    {
        return $this->expires_at && $this->expires_at->diffInDays(now()) <= $days;
    }

    public function canBeVerified(): bool
    {
        return $this->status === DocumentStatus::UPLOADED;
    }

    public function canBeRejected(): bool
    {
        return $this->status === DocumentStatus::UPLOADED;
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeExpired($query)
    {
        return $query
            ->where('expires_at', '<', now())
            ->where('status', '!=', DocumentStatus::EXPIRED);
    }

    public function scopeNearExpiry($query, int $days = 30)
    {
        return $query
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now());
    }

    public function scopePending($query)
    {
        return $query->where('status', DocumentStatus::UPLOADED);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', DocumentStatus::VERIFIED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', DocumentStatus::REJECTED);
    }
}
