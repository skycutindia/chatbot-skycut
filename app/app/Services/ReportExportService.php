<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\Website;

class ReportExportService
{
    public function __construct(
        protected AnalyticsService $analytics,
    ) {}

    /** @return array{organization: Organization, days: int, totals: array<string, int|float>, rows: list<array<string, mixed>>} */
    public function buildOrganizationReport(Organization $organization, int $days = 30): array
    {
        $websites = Website::where('organization_id', $organization->id)->get();

        $rows = $websites->map(function (Website $website) use ($days) {
            $summary = $this->analytics->summary($website, $days);

            return [
                'website' => $website->name,
                'conversations' => $summary['conversations'],
                'leads' => $summary['leads'],
                'handoffs' => $summary['handoffs'],
                'handoff_rate' => $summary['handoff_rate'],
                'ai_resolution_rate' => $summary['ai_resolution_rate'],
                'widget_opens' => $summary['widget_opens'],
                'messages' => $summary['messages'],
                'avg_satisfaction' => $summary['avg_satisfaction'],
            ];
        })->values()->all();

        return [
            'organization' => $organization,
            'days' => $days,
            'totals' => [
                'conversations' => collect($rows)->sum('conversations'),
                'leads' => Lead::where('organization_id', $organization->id)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->count(),
                'handoffs' => collect($rows)->sum('handoffs'),
                'widget_opens' => collect($rows)->sum('widget_opens'),
            ],
            'rows' => $rows,
        ];
    }

    /** @param list<array<string, mixed>> $rows */
    public function toCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'Website', 'Conversations', 'Leads', 'Handoffs', 'Handoff %',
            'AI Resolution %', 'Widget Opens', 'Messages', 'Avg Rating',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['website'],
                $row['conversations'],
                $row['leads'],
                $row['handoffs'],
                $row['handoff_rate'],
                $row['ai_resolution_rate'],
                $row['widget_opens'],
                $row['messages'],
                $row['avg_satisfaction'] ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    /** @param list<array<string, mixed>> $rows */
    public function toExcelXml(string $title, array $rows, array $totals, int $days): string
    {
        $escape = fn (mixed $value): string => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $header = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
<Worksheet ss:Name="Report">
<Table>
XML;

        $rowsXml = '<Row><Cell><Data ss:Type="String">'.$escape($title).'</Data></Cell></Row>';
        $rowsXml .= '<Row><Cell><Data ss:Type="String">Period: last '.$days.' days</Data></Cell></Row>';
        $rowsXml .= '<Row></Row>';
        $rowsXml .= '<Row><Cell><Data ss:Type="String">Total conversations</Data></Cell><Cell><Data ss:Type="Number">'.$totals['conversations'].'</Data></Cell></Row>';
        $rowsXml .= '<Row><Cell><Data ss:Type="String">Total leads</Data></Cell><Cell><Data ss:Type="Number">'.$totals['leads'].'</Data></Cell></Row>';
        $rowsXml .= '<Row></Row>';

        $columns = ['Website', 'Conversations', 'Leads', 'Handoffs', 'Handoff %', 'AI %', 'Widget Opens', 'Messages', 'Rating'];
        $rowsXml .= '<Row>';
        foreach ($columns as $column) {
            $rowsXml .= '<Cell><Data ss:Type="String">'.$escape($column).'</Data></Cell>';
        }
        $rowsXml .= '</Row>';

        foreach ($rows as $row) {
            $rowsXml .= '<Row>';
            $values = [
                $row['website'],
                $row['conversations'],
                $row['leads'],
                $row['handoffs'],
                $row['handoff_rate'],
                $row['ai_resolution_rate'],
                $row['widget_opens'],
                $row['messages'],
                $row['avg_satisfaction'] ?? '',
            ];
            foreach ($values as $index => $value) {
                $type = $index === 0 ? 'String' : (is_numeric($value) && $value !== '' ? 'Number' : 'String');
                $rowsXml .= '<Cell><Data ss:Type="'.$type.'">'.$escape($value).'</Data></Cell>';
            }
            $rowsXml .= '</Row>';
        }

        return $header.$rowsXml.'</Table></Worksheet></Workbook>';
    }

    /** @param list<array<string, mixed>> $rows */
    public function toPdfHtml(Organization $organization, array $rows, array $totals, int $days): string
    {
        return view('reports.pdf', [
            'organization' => $organization,
            'rows' => $rows,
            'totals' => $totals,
            'days' => $days,
            'generatedAt' => now(),
        ])->render();
    }

    /** @param list<array<string, mixed>> $rows */
    public function toPdfBinary(Organization $organization, array $rows, array $totals, int $days): ?string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return null;
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadHTML(
            $this->toPdfHtml($organization, $rows, $totals, $days)
        )->setPaper('a4', 'landscape')->output();
    }
}
