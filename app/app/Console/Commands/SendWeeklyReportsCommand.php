<?php

namespace App\Console\Commands;

use App\Mail\WeeklyReportMail;
use App\Models\Organization;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReportsCommand extends Command
{
    protected $signature = 'reports:weekly {--days=7 : Report period in days}';

    protected $description = 'Email weekly analytics summaries with CSV, Excel, and PDF attachments';

    public function handle(ReportExportService $exports): int
    {
        $days = (int) $this->option('days');

        Organization::where('is_active', true)->each(function (Organization $org) use ($exports, $days) {
            $report = $exports->buildOrganizationReport($org, $days);

            if ($report['rows'] === []) {
                return;
            }

            $recipients = collect();
            $notificationEmail = $org->settings['notification_email'] ?? null;
            if ($notificationEmail) {
                $recipients->push($notificationEmail);
            } else {
                $recipients = User::where('organization_id', $org->id)
                    ->whereIn('role', ['owner', 'admin'])
                    ->where('is_active', true)
                    ->pluck('email');
            }

            $recipients = $recipients->filter()->unique();

            if ($recipients->isEmpty()) {
                return;
            }

            $csv = $exports->toCsv($report['rows']);
            $excel = $exports->toExcelXml("Weekly report — {$org->name}", $report['rows'], $report['totals'], $days);
            $pdf = $exports->toPdfBinary($org, $report['rows'], $report['totals'], $days);

            foreach ($recipients as $email) {
                Mail::to($email)->send(new WeeklyReportMail($org, $days, $csv, $excel, $pdf));
            }

            $this->info("Sent report to {$org->name} ({$recipients->count()} recipient(s))");
        });

        return self::SUCCESS;
    }
}
