<?php

namespace App\Support;

use App\Enums\UserRole;

class RolePermissionMatrix
{
    /** @return list<array{role: string, label: string, websites: bool, inbox: bool, settings: bool, analytics: bool, read_only: bool}> */
    public static function rows(): array
    {
        return array_map(function (UserRole $role) {
            return [
                'role' => $role->value,
                'label' => $role->label(),
                'websites' => $role->canManageWebsites(),
                'inbox' => $role->canHandleLiveChat(),
                'settings' => $role->canManageOrganization(),
                'analytics' => $role->canViewAnalytics() && ! $role->isReadOnly(),
                'read_only' => $role->isReadOnly(),
            ];
        }, array_filter(
            UserRole::cases(),
            fn (UserRole $r) => ! $r->isPlatformLevel()
        ));
    }
}
