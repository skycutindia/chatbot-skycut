<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics): View
    {
        if ($request->user()->roleEnum()->isPlatformLevel()) {
            return view('admin.dashboard', [
                'stats' => [
                    'organizations' => Organization::count(),
                    'users' => User::whereNotNull('organization_id')->count(),
                    'websites' => Website::count(),
                    'conversations' => Conversation::count(),
                    'leads' => Lead::count(),
                    'open_chats' => Conversation::whereIn('status', ['awaiting_agent', 'open'])->count(),
                ],
                'recentOrganizations' => Organization::withCount(['websites', 'users'])->latest()->limit(10)->get(),
                'chartData' => $this->platformChartData(),
            ]);
        }

        $orgId = $request->user()->organization_id;
        $websites = Website::where('organization_id', $orgId)->with('configuration')->latest()->get();
        $websiteIds = $websites->pluck('id');

        $primaryWebsite = $websites->firstWhere('demo_slug', config('chatbot.demo_website_slug'))
            ?? $websites->first();

        $analyticsSummary = $primaryWebsite
            ? $analytics->summary($primaryWebsite, 30)
            : null;

        $stats = [
            'websites' => $websites->count(),
            'conversations' => $websiteIds->isEmpty() ? 0 : Conversation::whereIn('website_id', $websiteIds)->count(),
            'open_chats' => $websiteIds->isEmpty() ? 0 : Conversation::whereIn('website_id', $websiteIds)->whereIn('status', ['open', 'awaiting_agent'])->count(),
            'awaiting_agent' => $websiteIds->isEmpty() ? 0 : Conversation::whereIn('website_id', $websiteIds)->where('status', 'awaiting_agent')->count(),
            'active_bots' => $websites->where('is_active', true)->count(),
            'leads' => Lead::where('organization_id', $orgId)->count(),
            'satisfaction' => $analyticsSummary['avg_satisfaction'] ?? null,
            'handoff_rate' => $analyticsSummary['handoff_rate'] ?? 0,
            'ai_resolution' => $analyticsSummary['ai_resolution_rate'] ?? 0,
            'messages_30d' => $analyticsSummary['messages'] ?? 0,
        ];

        $recentConversations = $websiteIds->isEmpty()
            ? collect()
            : Conversation::query()
                ->with(['website:id,name', 'assignedUser:id,name'])
                ->whereIn('website_id', $websiteIds)
                ->latest('last_message_at')
                ->limit(8)
                ->get();

        $awaitingQueue = $websiteIds->isEmpty()
            ? collect()
            : Conversation::query()
                ->with('website:id,name')
                ->whereIn('website_id', $websiteIds)
                ->where('status', 'awaiting_agent')
                ->latest('last_message_at')
                ->limit(5)
                ->get();

        $recentLeads = Lead::where('organization_id', $orgId)->latest()->limit(5)->get();

        $chartData = $analytics->organizationConversationsPerDay($orgId, 7);
        $todayStats = $analytics->organizationTodayStats($orgId);

        return view('dashboard.index', compact(
            'websites',
            'stats',
            'primaryWebsite',
            'analyticsSummary',
            'recentConversations',
            'awaitingQueue',
            'recentLeads',
            'chartData',
            'todayStats',
        ));
    }

    /** @return list<array{date: string, label: string, count: int, height_pct: int}> */
    protected function platformChartData(): array
    {
        $result = [];
        $max = 1;

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Conversation::whereDate('created_at', $date->toDateString())->count();
            $max = max($max, $count);
            $result[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('D'),
                'count' => $count,
            ];
        }

        foreach ($result as &$row) {
            $row['height_pct'] = $max > 0 ? round(($row['count'] / $max) * 100) : 0;
        }

        return $result;
    }
}
