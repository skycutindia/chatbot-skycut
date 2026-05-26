<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InboxFilterPreset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboxFilterPresetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $presets = InboxFilterPreset::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'filters']);

        return response()->json([
            'presets' => $presets->map(fn (InboxFilterPreset $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'url' => route('inbox.index', $p->toQueryParams()),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'filters' => 'required|array',
            'filters.q' => 'nullable|string|max:255',
            'filters.website_id' => 'nullable|integer',
            'filters.department_id' => 'nullable|integer',
            'filters.sort' => 'nullable|in:newest,oldest,priority',
            'filters.view' => 'nullable|in:all,awaiting,assigned,starred,pinned',
        ]);

        $preset = InboxFilterPreset::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
            ],
            ['filters' => $this->normalizeFilters($validated['filters'])]
        );

        return response()->json([
            'preset' => [
                'id' => $preset->id,
                'name' => $preset->name,
                'url' => route('inbox.index', $preset->toQueryParams()),
            ],
        ], 201);
    }

    public function destroy(Request $request, InboxFilterPreset $preset): JsonResponse
    {
        abort_unless($preset->user_id === $request->user()->id, 404);
        $preset->delete();

        return response()->json(['ok' => true]);
    }

    /** @param  array<string, mixed>  $filters */
    protected function normalizeFilters(array $filters): array
    {
        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'website_id' => $filters['website_id'] ?? null,
            'department_id' => $filters['department_id'] ?? null,
            'sort' => $filters['sort'] ?? 'newest',
            'view' => $filters['view'] ?? 'all',
        ];
    }
}
