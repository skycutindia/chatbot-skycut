<?php

namespace App\Services;

use App\Events\ConversationMessageSent;
use App\Models\Conversation;
use App\Models\FileAttachment;
use App\Models\Message;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentService
{
    /** @var list<string> */
    protected array $allowedMimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip', 'application/x-zip-compressed',
        'audio/mpeg', 'audio/wav', 'audio/webm', 'audio/ogg',
        'video/mp4', 'video/webm', 'video/quicktime',
    ];

    public function maxBytes(): int
    {
        return (int) config('chatbot.attachments.max_bytes', 10 * 1024 * 1024);
    }

    public function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > $this->maxBytes()) {
            abort(422, 'File exceeds maximum size of '.round($this->maxBytes() / 1024 / 1024, 1).' MB.');
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';
        if (! in_array($mime, $this->allowedMimes, true)) {
            abort(422, 'File type not allowed.');
        }
    }

    public function storeForConversation(
        Conversation $conversation,
        UploadedFile $file,
        string $senderType,
        ?int $senderId = null,
        ?string $caption = null,
    ): Message {
        $this->validateFile($file);

        $websiteId = $conversation->website_id;
        $storedName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs(
            "chat-attachments/{$websiteId}/{$conversation->id}",
            $storedName,
            'local'
        );

        $message = $conversation->messages()->create([
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'content' => $caption ?: ('📎 '.$file->getClientOriginalName()),
            'source' => 'attachment',
        ]);

        FileAttachment::create([
            'message_id' => $message->id,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        $message = $message->fresh(['attachments']);

        if (config('broadcasting.default') !== 'null') {
            broadcast(new ConversationMessageSent($message));
        }

        return $message;
    }

    public function canAccess(FileAttachment $attachment, ?User $user = null, ?Website $website = null, ?string $visitorId = null): bool
    {
        $attachment->loadMissing('message.conversation.website');
        $conversation = $attachment->message->conversation;

        if ($user && $conversation->website->organization_id === $user->organization_id) {
            return true;
        }

        if ($website && $conversation->website_id === $website->id && $visitorId && $conversation->visitor_id === $visitorId) {
            return true;
        }

        return false;
    }

    public function download(FileAttachment $attachment): StreamedResponse
    {
        if (! Storage::disk('local')->exists($attachment->path)) {
            abort(404);
        }

        return Storage::disk('local')->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type ?? 'application/octet-stream',
        ]);
    }

    /** @return array<string, mixed> */
    public function formatAttachment(FileAttachment $attachment, bool $forWidget = false, ?Website $website = null): array
    {
        $url = $forWidget && $website
            ? route('api.widget.attachments.download', [
                'botToken' => $website->bot_token,
                'attachment' => $attachment->id,
            ])
            : route('chat.attachments.download', $attachment);

        return [
            'id' => $attachment->id,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size_bytes' => $attachment->size_bytes,
            'url' => $url,
            'is_image' => str_starts_with((string) $attachment->mime_type, 'image/'),
        ];
    }

    /** @return array<string, mixed> */
    public function formatMessage(
        Message $message,
        bool $forWidget = false,
        ?Website $website = null,
        ?string $reactorType = null,
        ?string $reactorKey = null,
    ): array {
        $message->loadMissing('attachments');
        $receipts = app(ChatReadReceiptService::class);
        $reactions = app(MessageReactionService::class);

        return [
            'id' => $message->id,
            'content' => $message->content,
            'sender_type' => $message->sender_type,
            'source' => $message->source,
            'created_at' => $message->created_at?->toIso8601String(),
            'delivered_at' => $message->delivered_at?->toIso8601String(),
            'read_at' => $message->read_at?->toIso8601String(),
            'receipt_status' => $receipts->receiptStatus($message),
            'reactions' => $reactions->summarizeForMessage($message->id, $reactorType, $reactorKey),
            'attachments' => $message->attachments
                ->map(fn (FileAttachment $a) => $this->formatAttachment($a, $forWidget, $website))
                ->values()
                ->all(),
        ];
    }
}
