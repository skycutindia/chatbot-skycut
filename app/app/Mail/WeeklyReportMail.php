<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public int $days,
        public string $csv,
        public string $excel,
        public ?string $pdf = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Weekly chatbot report — {$this->organization->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-report',
            with: [
                'organization' => $this->organization,
                'days' => $this->days,
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $date = now()->format('Y-m-d');
        $attachments = [
            Attachment::fromData(fn () => $this->csv, "chatbot-report-{$date}.csv")
                ->withMime('text/csv'),
            Attachment::fromData(fn () => $this->excel, "chatbot-report-{$date}.xls")
                ->withMime('application/vnd.ms-excel'),
        ];

        if ($this->pdf) {
            $attachments[] = Attachment::fromData(fn () => $this->pdf, "chatbot-report-{$date}.pdf")
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
