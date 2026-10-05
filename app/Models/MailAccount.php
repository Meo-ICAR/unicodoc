<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'protocol',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'last_synced_at',
        'is_active',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MailMessage::class);
    }
}
