<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'organization_id', 'name', 'email', 'avatar_url', 'password', 'role', 'is_active',
    'email_verified_at',
    'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
    'locked_until', 'failed_login_attempts', 'allowed_ips',
])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, MustVerifyEmail, Notifiable;

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function roleEnum(): \App\Enums\UserRole
    {
        return \App\Enums\UserRole::tryFrom($this->role) ?? \App\Enums\UserRole::Agent;
    }

    public function isOwnerOrAdmin(): bool
    {
        return $this->roleEnum()->canManageWebsites();
    }

    public function loginHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function socialAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function agentStatus(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AgentStatus::class);
    }

    public function departments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Department::class)->withTimestamps();
    }

    public function inboxFilterPresets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InboxFilterPreset::class);
    }

    public function agentQuickReplies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AgentQuickReply::class);
    }

    public function isLiveChatAgent(): bool
    {
        return $this->roleEnum()->canHandleLiveChat();
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function hasIpRestriction(): bool
    {
        return is_array($this->allowed_ips) && count($this->allowed_ips) > 0;
    }

    public function allowsIp(?string $ip): bool
    {
        if (! $this->hasIpRestriction() || ! $ip) {
            return true;
        }

        return in_array($ip, $this->allowed_ips, true);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function hasPendingTwoFactorSetup(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at === null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'allowed_ips' => 'array',
        ];
    }
}
