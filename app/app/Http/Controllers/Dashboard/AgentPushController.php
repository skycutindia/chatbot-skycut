<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AgentPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentPushController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        abort_unless($request->user()->roleEnum()->canHandleLiveChat(), 403);

        $validated = $request->validate([
            'endpoint' => 'required|string|max:512',
            'keys' => 'nullable|array',
            'keys.p256dh' => 'nullable|string',
            'keys.auth' => 'nullable|string',
        ]);

        AgentPushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint' => $validated['endpoint'],
            ],
            [
                'public_key' => $validated['keys']['p256dh'] ?? null,
                'auth_token' => $validated['keys']['auth'] ?? null,
                'content_encoding' => 'aesgcm',
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate(['endpoint' => 'required|string|max:512']);

        AgentPushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }
}
