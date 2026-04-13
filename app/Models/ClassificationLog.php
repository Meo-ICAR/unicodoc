<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ClassificationLog extends Model
{
    protected $fillable = [
        'document_id',
        'predicted_type_id',
        'actual_type_id',
        'classifier_used',
        'confidence_score',
        'is_override',
        'user_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'document_id' => 'string',
        'predicted_type_id' => 'integer',
        'actual_type_id' => 'integer',
        'confidence_score' => 'integer',
        'is_override' => 'boolean',
        'user_id' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function predictedType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'predicted_type_id');
    }

    public function actualType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'actual_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCorrect(): bool
    {
        return $this->predicted_type_id === $this->actual_type_id;
    }

    public function isIncorrect(): bool
    {
        return !$this->isCorrect();
    }

    public function isAIClassification(): bool
    {
        return $this->classifier_used === 'ai';
    }

    public function isRegexClassification(): bool
    {
        return $this->classifier_used === 'regex';
    }

    public function hasHighConfidence(): bool
    {
        return $this->confidence_score >= 90;
    }

    public function hasLowConfidence(): bool
    {
        return $this->confidence_score < 70;
    }

    public function getConfidenceLevel(): string
    {
        if ($this->confidence_score === null) {
            return 'N/A';
        }

        return match (true) {
            $this->confidence_score >= 95 => 'Molto Alta',
            $this->confidence_score >= 85 => 'Alta',
            $this->confidence_score >= 70 => 'Media',
            $this->confidence_score >= 50 => 'Bassa',
            default => 'Molto Bassa'
        };
    }

    public function scopeByDocument($query, $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeByClassifier($query, $classifier)
    {
        return $query->where('classifier_used', $classifier);
    }

    public function scopeCorrect($query)
    {
        return $query->whereColumn('predicted_type_id', 'actual_type_id');
    }

    public function scopeIncorrect($query)
    {
        return $query->whereColumn('predicted_type_id', '!=', 'actual_type_id');
    }

    public function scopeOverrides($query)
    {
        return $query->where('is_override', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByConfidence($query, $min, $max = null)
    {
        $query->where('confidence_score', '>=', $min);

        if ($max !== null) {
            $query->where('confidence_score', '<=', $max);
        }

        return $query;
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
