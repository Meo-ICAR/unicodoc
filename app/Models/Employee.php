<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

class Employee extends Model
{
    use SoftDeletes, Userstamps;

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'role_title',
        'cf',
        'email',
        'pec',
        'phone',
        'department',
        'oam',
        'oam_at',
        'oam_name',
        'numero_iscrizione_rui',
        'oam_dismissed_at',
        'ivass',
        'hiring_date',
        'termination_date',
        'company_branch_id',
        'coordinated_by_id',
        'employee_types',
        'supervisor_type',
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
    ];

    protected $casts = [
        'id' => 'integer',
        'company_id' => 'string',
        'user_id' => 'integer',
        'company_branch_id' => 'integer',
        'coordinated_by_id' => 'integer',
        'is_structure' => 'boolean',
        'is_ghost' => 'boolean',
        'oam_at' => 'date',
        'oam_dismissed_at' => 'date',
        'hiring_date' => 'date',
        'termination_date' => 'date',
    ];

    protected $dates = [
        'oam_at',
        'oam_dismissed_at',
        'hiring_date',
        'termination_date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function coordinatedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'coordinated_by_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function coordinatedEmployees(): HasMany
    {
        return $this->hasMany(Employee::class, 'coordinated_by_id');
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

    public function isActive(): bool
    {
        return is_null($this->termination_date);
    }

    public function isOAMActive(): bool
    {
        return !is_null($this->oam_at) && is_null($this->oam_dismissed_at);
    }

    public function isIVASSActive(): bool
    {
        return !is_null($this->ivass);
    }

    public function isSupervisor(): bool
    {
        return $this->supervisor_type !== 'no';
    }

    public function getFullName(): string
    {
        return $this->name;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('termination_date');
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('employee_types', $type);
    }

    public function scopeSupervisors($query)
    {
        return $query->where('supervisor_type', '!=', 'no');
    }
}
