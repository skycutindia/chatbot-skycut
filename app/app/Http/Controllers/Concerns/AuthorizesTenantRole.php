<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Website;
use Illuminate\Http\Request;

trait AuthorizesTenantRole
{
    protected function ensureCanManageWebsites(Request $request): void
    {
        abort_unless($request->user()->roleEnum()->canManageWebsites(), 403);
    }

    protected function ensureCanManageOrganization(Request $request): void
    {
        abort_unless($request->user()->roleEnum()->canManageOrganization(), 403);
    }

    protected function ensureCanWriteInbox(Request $request): void
    {
        $role = $request->user()->roleEnum();
        abort_unless($role->canHandleLiveChat() && ! $role->isReadOnly(), 403);
    }

    protected function ensureWebsiteInOrganization(Request $request, Website $website): void
    {
        abort_unless($website->organization_id === $request->user()->organization_id, 403);
    }
}
