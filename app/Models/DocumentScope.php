<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class DocumentScope extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color_code',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getBadgeColor(): string
    {
        return $this->color_code;
    }
}
