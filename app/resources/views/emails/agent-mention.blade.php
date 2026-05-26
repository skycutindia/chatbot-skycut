<!DOCTYPE html>
<html lang="en">
<body style="font-family: system-ui, sans-serif; color: #0f172a; line-height: 1.5;">
    <p><strong>{{ $mention->mentionedBy?->name }}</strong> mentioned you in an internal note on a live chat.</p>
    <p>
        <strong>Visitor:</strong> {{ $mention->conversation?->visitor_name ?: 'Visitor' }}<br>
        <strong>Website:</strong> {{ $mention->conversation?->website?->name }}
    </p>
    <blockquote style="margin: 1rem 0; padding: 0.75rem 1rem; background: #f8fafc; border-left: 4px solid #2563eb;">
        {{ $mention->note?->body }}
    </blockquote>
    <p>
        <a href="{{ route('inbox.index', ['conversation' => $mention->conversation_id]) }}" style="color:#2563eb;">Open conversation in Live Inbox</a>
    </p>
</body>
</html>
