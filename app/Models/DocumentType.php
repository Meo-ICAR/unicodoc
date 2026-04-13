<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Wildside\Userstamps\HasUserstamps;
use Mattiverse\Userstamps\Traits\Userstamps;

class DocumentType extends Model
{
    use SoftDeletes, Userstamps;

    protected $fillable = [
        'name',
        'description',
        'code',
        'codegroup',
        'slug',
        'regex_pattern',
        'priority',
        'phase',
        'is_person',
        'is_signed',
        'is_monitored',
        'is_company',
        'is_employee',
        'is_agent',
        'is_principal',
        'is_client',
        'is_practice',
        'duration',
        'emitted_by',
        'is_sensible',
        'is_template',
        'is_stored',
        'regex',
        'is_endmonth',
        'is_AiAbstract',
        'is_AiCheck',
        'AiPattern',
        'min_confidence',
        'allow_auto_verification',
        'notify_days_before',
        'retention_years',
    ];

    protected $casts = [
        'is_person' => 'boolean',
        'is_signed' => 'boolean',
        'is_monitored' => 'boolean',
        'is_company' => 'boolean',
        'is_employee' => 'boolean',
        'is_agent' => 'boolean',
        'is_principal' => 'boolean',
        'is_client' => 'boolean',
        'is_practice' => 'boolean',
        'is_sensible' => 'boolean',
        'is_template' => 'boolean',
        'is_stored' => 'boolean',
        'is_endmonth' => 'boolean',
        'is_AiAbstract' => 'boolean',
        'is_AiCheck' => 'boolean',
        'min_confidence' => 'integer',
        'allow_auto_verification' => 'boolean',
        'notify_days_before' => 'array',
        'retention_years' => 'integer',
    ];

    public function canAutoVerify($confidence): bool
    {
        return $this->allow_auto_verification && $confidence > 95;
    }

    public function meetsConfidenceThreshold($confidence): bool
    {
        return $confidence >= $this->min_confidence;
    }

    public function getNotifyDays(): array
    {
        return $this->notify_days_before ?? [];
    }

    public function hasRetentionPolicy(): bool
    {
        return !is_null($this->retention_years);
    }

    public function getRetentionDate($documentDate): ?\Carbon\Carbon
    {
        if (!$this->hasRetentionPolicy()) {
            return null;
        }

        return \Carbon\Carbon::parse($documentDate)->addYears($this->retention_years);
    }

    public function isExpired($documentDate): bool
    {
        if (!$this->hasRetentionPolicy()) {
            return false;
        }

        $retentionDate = $this->getRetentionDate($documentDate);
        return $retentionDate && $retentionDate->isPast();
    }

    public function getDaysUntilExpiry($documentDate): ?int
    {
        if (!$this->hasRetentionPolicy()) {
            return null;
        }

        $retentionDate = $this->getRetentionDate($documentDate);
        return $retentionDate ? $retentionDate->diffInDays(now(), false) : null;
    }

    public function shouldNotify($documentDate): array
    {
        $notifyDays = $this->getNotifyDays();
        $daysUntilExpiry = $this->getDaysUntilExpiry($documentDate);

        if ($daysUntilExpiry === null || $daysUntilExpiry < 0) {
            return [];
        }

        return array_filter($notifyDays, function ($days) use ($daysUntilExpiry) {
            return $daysUntilExpiry <= $days;
        });
    }

    public function scopeWithAutoVerification($query)
    {
        return $query->where('allow_auto_verification', true);
    }

    public function scopeWithRetentionPolicy($query)
    {
        return $query->whereNotNull('retention_years');
    }

    public function scopeByMinConfidence($query, $confidence)
    {
        return $query->where('min_confidence', '<=', $confidence);
    }
}
