<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Agent = 'agent';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Agent => 'Agent',
            self::Viewer => 'Viewer',
        };
    }

    public function isPlatformLevel(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canManageWebsites(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Owner, self::Admin, self::Manager], true);
    }

    public function canManageOrganization(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Owner, self::Admin], true);
    }

    public function canManageUsers(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Owner, self::Admin], true);
    }

    public function canHandleLiveChat(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Owner, self::Admin, self::Manager, self::Agent], true);
    }

    public function canViewAnalytics(): bool
    {
        return $this !== self::Agent || true;
    }

    public function isReadOnly(): bool
    {
        return $this === self::Viewer;
    }

    /** @return list<string> */
    public static function tenantRoles(): array
    {
        return array_map(
            fn (self $r) => $r->value,
            array_filter(self::cases(), fn (self $r) => ! $r->isPlatformLevel())
        );
    }
}
