<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

class Client extends Model
{
    use SoftDeletes, Userstamps;

    protected $fillable = [
        'company_id',
        'is_person',
        'name',
        'first_name',
        'tax_code',
        'vat_number',
        'roc_registration_number',
        'email',
        'dpo_email',
        'privacy_policy_url',
        'contract_signed_at',
        'phone',
        'is_pep',
        'is_sanctioned',
        'is_remote_interaction',
        'general_consent_at',
        'privacy_policy_read_at',
        'consent_special_categories_at',
        'consent_sic_at',
        'consent_marketing_at',
        'consent_profiling_at',
        'privacy_role',
        'purpose',
        'data_subjects',
        'data_categories',
        'retention_period',
        'extra_eu_transfer',
        'security_measures',
        'privacy_data',
        'is_structure',
        'is_ghost',
        'client_type_id',
        'status',
        'is_company',
        'is_lead',
        'leadsource_id',
        'acquired_at',
        'contoCOGE',
        'privacy_consent',
        'is_client',
        'subfornitori',
        'is_requiredApprovation',
        'is_approved',
        'is_anonymous',
        'blacklist_at',
        'blacklisted_by',
        'salary',
        'salary_quote',
        'is_art108',
        'user_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'company_id' => 'string',
        'is_person' => 'boolean',
        'is_pep' => 'boolean',
        'is_sanctioned' => 'boolean',
        'is_remote_interaction' => 'boolean',
        'is_structure' => 'boolean',
        'is_ghost' => 'boolean',
        'is_company' => 'boolean',
        'is_lead' => 'boolean',
        'privacy_consent' => 'boolean',
        'is_client' => 'boolean',
        'is_requiredApprovation' => 'boolean',
        'is_approved' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_art108' => 'boolean',
        'contract_signed_at' => 'datetime',
        'general_consent_at' => 'datetime',
        'privacy_policy_read_at' => 'datetime',
        'consent_special_categories_at' => 'datetime',
        'consent_sic_at' => 'datetime',
        'consent_marketing_at' => 'datetime',
        'consent_profiling_at' => 'datetime',
        'acquired_at' => 'datetime',
        'blacklist_at' => 'datetime',
        'salary' => 'decimal:2',
        'salary_quote' => 'decimal:2',
    ];

    protected $dates = [
        'contract_signed_at',
        'general_consent_at',
        'privacy_policy_read_at',
        'consent_special_categories_at',
        'consent_sic_at',
        'consent_marketing_at',
        'consent_profiling_at',
        'acquired_at',
        'blacklist_at',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function clientType(): BelongsTo
    {
        return $this->belongsTo(ClientType::class);
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'leadsource_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function isBlacklisted(): bool
    {
        return !is_null($this->blacklist_at);
    }

    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    public function isLead(): bool
    {
        return $this->is_lead;
    }

    public function getFullName(): string
    {
        if ($this->is_person) {
            return trim($this->first_name . ' ' . $this->name);
        }
        return $this->name;
    }

    public function scopeActive($query)
    {
        return $query
            ->where('is_approved', true)
            ->whereNull('blacklist_at');
    }

    public function scopeLeads($query)
    {
        return $query->where('is_lead', true);
    }

    public function scopeBlacklisted($query)
    {
        return $query->whereNotNull('blacklist_at');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
