<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Organization;
use Illuminate\Support\Str;

class ChatTranscriptService
{
    public function load(Conversation $conversation): Conversation
    {
        return $conversation->load([
            'website.organization',
            'assignedUser',
            'department',
            'rating',
            'messages.attachments',
            'internalNotes.user',
        ]);
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: string, 4: string}> */
    public function csvRows(Conversation $conversation): array
    {
        $conversation = $this->load($conversation);
        $rows = [];

        foreach ($conversation->messages as $message) {
            $rows[] = [
                $message->created_at?->toDateTimeString() ?? '',
                $this->senderLabel($message, $conversation),
                $this->messageBody($message),
                $message->source ?? '',
                $this->attachmentSummary($message),
            ];
        }

        return $rows;
    }

    public function plainText(Conversation $conversation): string
    {
        $conversation = $this->load($conversation);
        $lines = [$this->headerBlock($conversation), ''];

        foreach ($conversation->messages as $message) {
            $lines[] = sprintf(
                '[%s] %s: %s%s',
                $message->created_at?->format('Y-m-d H:i:s') ?? '',
                $this->senderLabel($message, $conversation),
                $this->messageBody($message),
                $this->attachmentSummary($message) !== '' ? ' '.$this->attachmentSummary($message) : ''
            );
        }

        if ($conversation->internalNotes->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '--- Internal notes ---';
            foreach ($conversation->internalNotes as $note) {
                $lines[] = sprintf(
                    '[%s] %s: %s',
                    $note->created_at?->format('Y-m-d H:i:s') ?? '',
                    $note->user?->name ?? 'Agent',
                    $note->body
                );
            }
        }

        return implode("\n", $lines);
    }

    public function pdfHtml(Conversation $conversation, Organization $organization): string
    {
        $conversation = $this->load($conversation);

        return view('exports.chat-transcript-pdf', [
            'organization' => $organization,
            'conversation' => $conversation,
            'generatedAt' => now(),
            'senderLabel' => fn (Message $message) => $this->senderLabel($message, $conversation),
            'messageBody' => fn (Message $message) => $this->messageBody($message),
            'attachmentSummary' => fn (Message $message) => $this->attachmentSummary($message),
        ])->render();
    }

    public function pdfBinary(Conversation $conversation, Organization $organization): ?string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return null;
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadHTML(
            $this->pdfHtml($conversation, $organization)
        )->setPaper('a4', 'portrait')->output();
    }

    public function filename(Conversation $conversation, string $extension): string
    {
        $slug = Str::slug($conversation->visitor_name ?: 'visitor');

        return sprintf(
            'chat-transcript-%d-%s-%s.%s',
            $conversation->id,
            $slug !== '' ? $slug : 'visitor',
            now()->format('Y-m-d'),
            ltrim($extension, '.')
        );
    }

    protected function headerBlock(Conversation $conversation): string
    {
        return implode("\n", array_filter([
            'Chat transcript',
            'Organization: '.$conversation->website?->organization?->name,
            'Website: '.$conversation->website?->name,
            'Conversation #'.$conversation->id,
            'Visitor: '.($conversation->visitor_name ?: 'Visitor'),
            $conversation->visitor_email ? 'Email: '.$conversation->visitor_email : null,
            $conversation->visitor_phone ? 'Phone: '.$conversation->visitor_phone : null,
            'Status: '.$conversation->status,
            'Mode: '.$conversation->mode,
            $conversation->assignedUser ? 'Agent: '.$conversation->assignedUser->name : null,
            $conversation->department ? 'Department: '.$conversation->department->name : null,
            'Started: '.$conversation->created_at?->format('Y-m-d H:i:s'),
            $conversation->closed_at ? 'Closed: '.$conversation->closed_at->format('Y-m-d H:i:s') : null,
        ]));
    }

    protected function senderLabel(Message $message, Conversation $conversation): string
    {
        return match ($message->sender_type) {
            'visitor' => $conversation->visitor_name ?: 'Visitor',
            'agent' => $conversation->assignedUser?->name ?? 'Agent',
            'bot' => 'AI Assistant',
            default => ucfirst((string) $message->sender_type),
        };
    }

    protected function messageBody(Message $message): string
    {
        if ($message->content && $message->source !== 'attachment') {
            return trim((string) $message->content);
        }

        if ($message->attachments->isNotEmpty()) {
            return '[Attachment]';
        }

        return '';
    }

    protected function attachmentSummary(Message $message): string
    {
        if ($message->attachments->isEmpty()) {
            return '';
        }

        $names = $message->attachments->pluck('original_name')->filter()->implode(', ');

        return $names !== '' ? '[Files: '.$names.']' : '';
    }
}
