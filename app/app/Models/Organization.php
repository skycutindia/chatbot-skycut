<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name', 'slug', 'settings', 'is_active',
        'logo_url', 'timezone',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function cannedResponses(): HasMany
    {
        return $this->hasMany(CannedResponse::class);
    }

    public function whatsappChannel(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WhatsappChannel::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(OrganizationInvite::class);
    }
}

