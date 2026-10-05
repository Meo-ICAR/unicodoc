<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class Principal extends Model
{
    protected $fillable = [
        'name',
        'abi',
        'abi_name',
        'stipulated_at',
        'dismissed_at',
        'vat_number',
        'vat_name',
        'type',
        'oam',
        'oam_name',
        'oam_at',
        'numero_iscrizione_rui',
        'ivass',
        'ivass_at',
        'ivass_name',
        'ivass_section',
        'is_active',
        'company_id',
        'mandate_number',
        'start_date',
        'end_date',
        'is_exclusive',
        'status',
        'is_dummy',
        'notes',
        'principal_type',
        'submission_type',
        'website',
        'portalsite',
        'contoCOGE',
        'is_reported',
    ];

    protected $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
        'is_exclusive' => 'boolean',
        'is_dummy' => 'boolean',
        'is_reported' => 'boolean',
        'company_id' => 'string',
        'oam_at' => 'date',
        'ivass_at' => 'date',
        'stipulated_at' => 'date',
        'dismissed_at' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $dates = [
        'oam_at',
        'ivass_at',
        'stipulated_at',
        'dismissed_at',
        'start_date',
        'end_date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function isActive(): bool
    {
        return $this->is_active &&
            $this->status === 'ATTIVO' &&
            (is_null($this->end_date) || $this->end_date >= now());
    }

    public function isOAMActive(): bool
    {
        return !is_null($this->oam) && !is_null($this->oam_at);
    }

    public function isIVASSActive(): bool
    {
        return !is_null($this->ivass) && !is_null($this->ivass_at);
    }

    public function isExpired(): bool
    {
        return !is_null($this->end_date) && $this->end_date < now();
    }

    public function isBank(): bool
    {
        return $this->principal_type === 'banca';
    }

    public function isInsuranceAgent(): bool
    {
        return $this->principal_type === 'agente_assicurativo';
    }

    public function isCaptiveAgent(): bool
    {
        return $this->principal_type === 'agente_captive';
    }

    public function getFullName(): string
    {
        return $this->name;
    }

    public function getFormattedStatus(): string
    {
        return match ($this->status) {
            'ATTIVO' => 'Attivo',
            'SCADUTO' => 'Scaduto',
            'RECEDUTO' => 'Receduto',
            'SOPESO' => 'Sospeso',
            default => $this->status,
        };
    }

    public function scopeActive($query)
    {
        return $query
            ->where('is_active', true)
            ->where('status', 'ATTIVO')
            ->where(function ($q) {
                $q
                    ->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeInactive($query)
    {
        return $query->where(function ($q) {
            $q
                ->where('is_active', false)
                ->orWhere('status', '!=', 'ATTIVO')
                ->orWhere('end_date', '<', now());
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('principal_type', $type);
    }

    public function scopeBanks($query)
    {
        return $query->where('principal_type', 'banca');
    }

    public function scopeInsurance($query)
    {
        return $query->whereIn('principal_type', ['agente_assicurativo', 'agente_captive']);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeWithPortal($query)
    {
        return $query->whereNotNull('portalsite');
    }

    public function scopeExclusive($query)
    {
        return $query->where('is_exclusive', true);
    }

    public function scopeReported($query)
    {
        return $query->where('is_reported', true);
    }
}
