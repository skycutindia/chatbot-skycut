<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Services\AnalyticsService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function show(Website $website, AnalyticsService $analytics): View
    {
        $summary = $analytics->summary($website);

        return view('dashboard.analytics.show', compact('website', 'summary'));
    }

    public function export(Website $website, AnalyticsService $analytics): StreamedResponse
    {
        $summary = $analytics->summary($website);

        return response()->streamDownload(function () use ($website, $summary) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Metric', 'Value']);
            foreach ($summary as $key => $value) {
                if ($key === 'events') {
                    continue;
                }
                fputcsv($out, [$key, is_scalar($value) ? $value : json_encode($value)]);
            }
            fclose($out);
        }, 'analytics-website-'.$website->id.'-'.now()->format('Y-m-d').'.csv');
    }
}
