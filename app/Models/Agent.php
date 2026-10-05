<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'name',
        'email',
        'pec',
        'phone',
        'description',
        'supervisor_type',
        'oam',
        'oam_at',
        'oam_name',
        'numero_iscrizione_rui',
        'ivass',
        'ivass_at',
        'ivass_name',
        'ivass_section',
        'stipulated_at',
        'dismissed_at',
        'type',
        'contribute',
        'contributeFrequency',
        'contributeFrom',
        'remburse',
        'vat_number',
        'vat_name',
        'enasarco',
        'is_active',
        'is_art108',
        'contoCOGE',
        'company_branch_id',
        'company_id',
        'coordinated_by_id',
        'coordinated_by_agent_id',
        'user_id',
        'oam_dismissed_at',
        'welcome_bonus',
        'campagna',
        'available_at',
        'budget',
        'email_personal',
        'tax_code',
    ];

    protected $casts = [
        'id' => 'integer',
        'contribute' => 'decimal:2',
        'contributeFrequency' => 'integer',
        'remburse' => 'decimal:2',
        'welcome_bonus' => 'decimal:2',
        'budget' => 'decimal:2',
        'is_active' => 'boolean',
        'is_art108' => 'boolean',
        'company_branch_id' => 'integer',
        'coordinated_by_id' => 'integer',
        'coordinated_by_agent_id' => 'integer',
        'user_id' => 'integer',
        'oam_at' => 'date',
        'ivass_at' => 'date',
        'stipulated_at' => 'date',
        'dismissed_at' => 'date',
        'contributeFrom' => 'date',
        'oam_dismissed_at' => 'date',
        'available_at' => 'date',
    ];

    protected $dates = [
        'oam_at',
        'ivass_at',
        'stipulated_at',
        'dismissed_at',
        'contributeFrom',
        'oam_dismissed_at',
        'available_at',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function coordinatedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'coordinated_by_id');
    }

    public function coordinatedByAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'coordinated_by_agent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function coordinatedAgents(): HasMany
    {
        return $this->hasMany(Agent::class, 'coordinated_by_agent_id');
    }

    public function isActive(): bool
    {
        return $this->is_active && is_null($this->dismissed_at);
    }

    public function isOAMActive(): bool
    {
        return !is_null($this->oam_at) && is_null($this->oam_dismissed_at);
    }

    public function isIVASSActive(): bool
    {
        return !is_null($this->ivass) && !is_null($this->ivass_at);
    }

    public function isSupervisor(): bool
    {
        return $this->supervisor_type !== 'no';
    }

    public function isBranchSupervisor(): bool
    {
        return $this->supervisor_type === 'filiale';
    }

    public function getFullName(): string
    {
        return $this->name;
    }

    public function scopeActive($query)
    {
        return $query
            ->where('is_active', true)
            ->whereNull('dismissed_at');
    }

    public function scopeInactive($query)
    {
        return $query->where(function ($q) {
            $q
                ->where('is_active', false)
                ->orWhereNotNull('dismissed_at');
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSupervisors($query)
    {
        return $query->where('supervisor_type', '!=', 'no');
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q
                ->whereNull('available_at')
                ->orWhere('available_at', '<=', now());
        });
    }
}
