<?php

namespace App\View\Composers;

use App\Models\Conversation;
use App\Services\AiConfigService;
use App\Services\ChatMentionService;
use Illuminate\View\View;

class DashboardComposer
{
    public function compose(View $view): void
    {
        if (! auth()->check() || auth()->user()->roleEnum()->isPlatformLevel()) {
            $view->with('awaitingCount', 0);
            $view->with('mentionCount', 0);
            $view->with('dashModules', app(AiConfigService::class)->defaultDashboardModules());

            return;
        }

        $orgId = auth()->user()->organization_id;
        $organization = auth()->user()->organization;
        $view->with('dashModules', app(AiConfigService::class)->dashboardModules($organization));

        $awaitingCount = Conversation::query()
            ->where('status', 'awaiting_agent')
            ->when($orgId, fn ($q) => $q->whereHas('website', fn ($w) => $w->where('organization_id', $orgId)))
            ->count();

        $mentionCount = auth()->user()->roleEnum()->canHandleLiveChat()
            ? app(ChatMentionService::class)->unreadCount(auth()->user())
            : 0;

        $view->with('awaitingCount', $awaitingCount);
        $view->with('mentionCount', $mentionCount);
    }
}
