<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AgentPresenceStatus;
use App\Http\Controllers\Controller;
use App\Services\AgentPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentPresenceController extends Controller
{
    public function update(Request $request, AgentPresenceService $presence): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:online,away,busy,offline',
        ]);

        $enum = AgentPresenceStatus::from($validated['status']);
        $status = $presence->setStatus($request->user(), $enum);

        return response()->json([
            'status' => $status->status,
            'label' => $status->statusEnum()->label(),
        ]);
    }
}
