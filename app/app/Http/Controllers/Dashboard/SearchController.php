<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse
    {
        $query = trim($request->string('q')->toString());
        $orgId = $request->user()->organization_id;
        $results = $this->search($query, $orgId, $request->user()->roleEnum()->isPlatformLevel());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['results' => $results]);
        }

        return view('dashboard.search.index', [
            'query' => $query,
            'results' => $results,
        ]);
    }

    /** @return list<array{type: string, title: string, subtitle: string, url: string}> */
    protected function search(string $query, ?int $orgId, bool $platformLevel): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $like = '%'.$query.'%';
        $results = [];

        $websites = Website::query()
            ->when(! $platformLevel && $orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('domain', 'like', $like)
                    ->orWhere('demo_slug', 'like', $like);
            })
            ->limit(5)
            ->get();

        foreach ($websites as $website) {
            $results[] = [
                'type' => 'Website',
                'title' => $website->name,
                'subtitle' => $website->domain ?: 'Chatbot',
                'url' => $website->demo_slug
                    ? route('websites.edit', $website)
                    : route('websites.show', $website),
            ];
        }

        $conversations = Conversation::query()
            ->with('website:id,name,organization_id,demo_slug')
            ->when(! $platformLevel && $orgId, fn ($q) => $q->whereHas(
                'website',
                fn ($w) => $w->where('organization_id', $orgId)
            ))
            ->where(function ($q) use ($like) {
                $q->where('visitor_name', 'like', $like)
                    ->orWhere('visitor_email', 'like', $like)
                    ->orWhere('visitor_id', 'like', $like);
            })
            ->latest('last_message_at')
            ->limit(5)
            ->get();

        foreach ($conversations as $conversation) {
            if (! $conversation->website) {
                continue;
            }
            $results[] = [
                'type' => 'Conversation',
                'title' => $conversation->visitor_name ?: 'Visitor '.$conversation->visitor_id,
                'subtitle' => $conversation->website->name.' · '.$conversation->status,
                'url' => route('websites.conversations.show', [$conversation->website, $conversation]),
            ];
        }

        $leads = Lead::query()
            ->when(! $platformLevel && $orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('company', 'like', $like);
            })
            ->latest()
            ->limit(5)
            ->get();

        foreach ($leads as $lead) {
            $results[] = [
                'type' => 'Lead',
                'title' => $lead->name ?: $lead->email ?: 'Lead #'.$lead->id,
                'subtitle' => $lead->email ?: ($lead->company ?: 'CRM'),
                'url' => route('leads.show', $lead),
            ];
        }

        return $results;
    }
}
