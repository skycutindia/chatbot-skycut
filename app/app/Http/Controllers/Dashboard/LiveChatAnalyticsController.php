<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\LiveChatAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LiveChatAnalyticsController extends Controller
{
    public function index(Request $request, LiveChatAnalyticsService $analytics): View
    {
        $days = (int) $request->query('days', 30);
        $orgId = $request->user()->organization_id;

        $summary = $analytics->organizationSummary($orgId, $days);
        $agents = $analytics->agentPerformance($orgId, $days);
        $chart = $analytics->chatsPerDay($orgId, 7);
        $statusBreakdown = $analytics->statusBreakdown($orgId);

        return view('dashboard.live-chat.analytics', compact('summary', 'agents', 'chart', 'statusBreakdown', 'days'));
    }

    public function export(Request $request, LiveChatAnalyticsService $analytics): StreamedResponse
    {
        $days = (int) $request->query('days', 30);
        $summary = $analytics->organizationSummary($request->user()->organization_id, $days);
        $agents = $analytics->agentPerformance($request->user()->organization_id, $days);

        return response()->streamDownload(function () use ($summary, $agents) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Metric', 'Value']);
            foreach ($summary as $key => $value) {
                fputcsv($out, [$key, is_scalar($value) ? $value : json_encode($value)]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Agent', 'Assigned', 'Messages', 'Avg rating']);
            foreach ($agents as $agent) {
                fputcsv($out, [$agent['name'], $agent['assigned'], $agent['messages'], $agent['avg_rating'] ?? '']);
            }
            fclose($out);
        }, 'live-chat-analytics-'.now()->format('Y-m-d').'.csv');
    }
}
