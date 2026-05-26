<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\FileAttachment;
use App\Services\ChatAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController extends Controller
{
    public function upload(Request $request, Conversation $conversation, ChatAttachmentService $attachments): JsonResponse
    {
        abort_unless($conversation->website->organization_id === $request->user()->organization_id, 403);

        $request->validate([
            'file' => 'required|file|max:'.(int) (config('chatbot.attachments.max_bytes', 10485760) / 1024),
            'caption' => 'nullable|string|max:500',
        ]);

        $message = $attachments->storeForConversation(
            $conversation,
            $request->file('file'),
            'agent',
            $request->user()->id,
            $request->input('caption'),
        );

        $conversation->update([
            'assigned_user_id' => $request->user()->id,
            'status' => 'open',
            'mode' => 'human',
            'agent_unread_count' => 0,
        ]);

        if (! $conversation->first_response_at) {
            $conversation->update(['first_response_at' => now()]);
        }

        return response()->json([
            'message' => $attachments->formatMessage($message),
        ]);
    }

    public function download(Request $request, FileAttachment $attachment, ChatAttachmentService $attachments): StreamedResponse
    {
        abort_unless($attachments->canAccess($attachment, $request->user()), 403);

        return $attachments->download($attachment);
    }
}
