<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class RegulatoryBody extends Model
{
    protected $fillable = [
        'name',
        'acronym',
        'official_website',
        'pec_address',
        'portal_url',
        'contact_person',
        'phone_support',
        'notes',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function principals(): HasMany
    {
        return $this->hasMany(Principal::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function isOAM(): bool
    {
        return $this->acronym === 'OAM';
    }

    public function isIVASS(): bool
    {
        return $this->acronym === 'IVASS';
    }

    public function isGPDP(): bool
    {
        return $this->acronym === 'GPDP';
    }

    public function hasPortal(): bool
    {
        return !is_null($this->portal_url);
    }

    public function hasPEC(): bool
    {
        return !is_null($this->pec_address);
    }

    public function getWebsiteUrl(): ?string
    {
        if (empty($this->official_website)) {
            return null;
        }

        return str_starts_with($this->official_website, 'http')
            ? $this->official_website
            : 'https://' . $this->official_website;
    }

    public function getPortalUrl(): ?string
    {
        if (empty($this->portal_url)) {
            return null;
        }

        return str_starts_with($this->portal_url, 'http')
            ? $this->portal_url
            : 'https://' . $this->portal_url;
    }

    public function getFormattedPhone(): ?string
    {
        if (empty($this->phone_support)) {
            return null;
        }

        return preg_replace('/^(\+39)?\s*(\d{2,3})\s*(\d{3,4})\s*(\d{3,4})$/', '$2 $3 $4', $this->phone_support);
    }

    public function scopeByAcronym($query, $acronym)
    {
        return $query->where('acronym', $acronym);
    }

    public function scopeWithPortal($query)
    {
        return $query->whereNotNull('portal_url');
    }

    public function scopeWithPEC($query)
    {
        return $query->whereNotNull('pec_address');
    }

    public function scopeByContact($query, $contact)
    {
        return $query->where('contact_person', 'like', "%{$contact}%");
    }

    public static function findByAcronym($acronym): ?self
    {
        return static::where('acronym', $acronym)->first();
    }

    public static function getOAM(): ?self
    {
        return static::findByAcronym('OAM');
    }

    public static function getIVASS(): ?self
    {
        return static::findByAcronym('IVASS');
    }

    public static function getGPDP(): ?self
    {
        return static::findByAcronym('GPDP');
    }
}
