<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ChatTranscriptService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatTranscriptController extends Controller
{
    public function csv(Conversation $conversation, ChatTranscriptService $transcripts): StreamedResponse
    {
        $this->authorizeConversation($conversation);

        $filename = $transcripts->filename($conversation, 'csv');

        return response()->streamDownload(function () use ($transcripts, $conversation) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Timestamp', 'Sender', 'Message', 'Source', 'Attachments']);

            foreach ($transcripts->csvRows($conversation) as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function txt(Conversation $conversation, ChatTranscriptService $transcripts): Response
    {
        $this->authorizeConversation($conversation);

        return response($transcripts->plainText($conversation), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$transcripts->filename($conversation, 'txt').'"',
        ]);
    }

    public function pdf(Conversation $conversation, ChatTranscriptService $transcripts): Response
    {
        $this->authorizeConversation($conversation);

        $organization = auth()->user()->organization;
        abort_unless($organization, 404);

        $binary = $transcripts->pdfBinary($conversation, $organization);
        abort_unless($binary, 503, 'PDF export is unavailable.');

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$transcripts->filename($conversation, 'pdf').'"',
        ]);
    }

    protected function authorizeConversation(Conversation $conversation): void
    {
        abort_unless(
            $conversation->website && $conversation->website->organization_id === auth()->user()->organization_id,
            403
        );
    }
}
