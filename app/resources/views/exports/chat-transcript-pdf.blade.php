<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Chat transcript #{{ $conversation->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; line-height: 1.5; }
        .meta span { display: block; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .sender { font-weight: bold; white-space: nowrap; }
        .attachments { color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Chat transcript</h1>
    <div class="meta">
        <span><strong>{{ $organization->name }}</strong></span>
        <span>Website: {{ $conversation->website?->name }}</span>
        <span>Conversation #{{ $conversation->id }} · {{ ucfirst($conversation->status) }} · {{ ucfirst($conversation->mode) }} mode</span>
        <span>Visitor: {{ $conversation->visitor_name ?: 'Visitor' }}@if($conversation->visitor_email) · {{ $conversation->visitor_email }}@endif</span>
        @if($conversation->assignedUser)
            <span>Agent: {{ $conversation->assignedUser->name }}</span>
        @endif
        @if($conversation->department)
            <span>Department: {{ $conversation->department->name }}</span>
        @endif
        <span>Generated {{ $generatedAt->format('M j, Y g:i A') }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 14%">Time</th>
                <th style="width: 16%">Sender</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            @forelse($conversation->messages as $message)
                <tr>
                    <td>{{ $message->created_at?->format('M j, Y H:i') }}</td>
                    <td class="sender">{{ $senderLabel($message) }}</td>
                    <td>
                        {{ $messageBody($message) }}
                        @if($attachmentSummary($message))
                            <div class="attachments">{{ $attachmentSummary($message) }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">No messages in this conversation.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($conversation->internalNotes->isNotEmpty())
        <h2 style="font-size: 14px; margin-top: 24px;">Internal notes</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 14%">Time</th>
                    <th style="width: 16%">Agent</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach($conversation->internalNotes as $note)
                    <tr>
                        <td>{{ $note->created_at?->format('M j, Y H:i') }}</td>
                        <td class="sender">{{ $note->user?->name ?? 'Agent' }}</td>
                        <td>{{ $note->body }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
