<?php

namespace App\Models\BPM;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'company_id',
        'role',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'company_id' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'manager']);
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }
}
