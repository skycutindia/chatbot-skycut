<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Website;
use App\Services\AnalyticsService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics): View
    {
        $orgId = $request->user()->organization_id;
        $websites = Website::where('organization_id', $orgId)->get();
        $days = (int) $request->query('days', 30);

        $summaries = $websites->map(fn (Website $w) => array_merge(
            ['website' => $w],
            $analytics->summary($w, $days)
        ));

        $totals = [
            'conversations' => $summaries->sum('conversations'),
            'leads' => Lead::where('organization_id', $orgId)->where('created_at', '>=', now()->subDays($days))->count(),
            'handoffs' => $summaries->sum('handoffs'),
            'widget_opens' => $summaries->sum('widget_opens'),
        ];

        return view('dashboard.reports.index', compact('summaries', 'totals', 'days'));
    }

    public function export(Request $request, ReportExportService $exports): Response|StreamedResponse
    {
        $organization = Organization::findOrFail($request->user()->organization_id);
        $days = (int) $request->query('days', 30);
        $format = $request->query('format', 'csv');

        $report = $exports->buildOrganizationReport($organization, $days);
        $date = now()->format('Y-m-d');
        $basename = "analytics-report-{$date}";

        return match ($format) {
            'excel', 'xls' => response($exports->toExcelXml(
                "Report — {$organization->name}",
                $report['rows'],
                $report['totals'],
                $days
            ), 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => "attachment; filename=\"{$basename}.xls\"",
            ]),
            'pdf' => $this->pdfResponse($exports, $organization, $report, $days, $basename),
            default => response()->streamDownload(
                fn () => print($exports->toCsv($report['rows'])),
                "{$basename}.csv",
                ['Content-Type' => 'text/csv; charset=UTF-8']
            ),
        };
    }

    /** @param array{rows: list<array<string, mixed>>, totals: array<string, int|float>} $report */
    protected function pdfResponse(
        ReportExportService $exports,
        Organization $organization,
        array $report,
        int $days,
        string $basename,
    ): Response {
        $pdf = $exports->toPdfBinary($organization, $report['rows'], $report['totals'], $days);

        if ($pdf) {
            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$basename}.pdf\"",
            ]);
        }

        return response($exports->toPdfHtml($organization, $report['rows'], $report['totals'], $days), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$basename}.html\"",
        ]);
    }
}
