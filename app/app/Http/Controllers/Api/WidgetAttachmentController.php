<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\FileAttachment;
use App\Models\Website;
use App\Services\ChatAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WidgetAttachmentController extends Controller
{
    public function store(Request $request, ChatAttachmentService $attachments): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'conversation_id' => 'required|integer',
            'file' => 'required|file|max:'.(int) (config('chatbot.attachments.max_bytes', 10485760) / 1024),
            'caption' => 'nullable|string|max:500',
        ]);

        $conversation = Conversation::query()
            ->where('website_id', $website->id)
            ->where('visitor_id', $validated['visitor_id'])
            ->where('id', $validated['conversation_id'])
            ->firstOrFail();

        $message = $attachments->storeForConversation(
            $conversation,
            $request->file('file'),
            'visitor',
            null,
            $validated['caption'] ?? null,
        );

        return response()->json([
            'message' => $attachments->formatMessage($message, true, $website),
        ]);
    }

    public function download(Request $request, FileAttachment $attachment, ChatAttachmentService $attachments): StreamedResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('website');

        $visitorId = $request->query('visitor_id');
        abort_unless($attachments->canAccess($attachment, null, $website, $visitorId), 403);

        return $attachments->download($attachment);
    }
}
