<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $query = Lead::query()->with(['website', 'assignedUser']);

        if ($request->user()->organization_id) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        $leads = $query
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.leads.index', [
            'leads' => $leads,
            'statuses' => LeadStatus::cases(),
        ]);
    }

    public function show(Lead $lead): View
    {
        abort_unless($lead->organization_id === auth()->user()->organization_id, 403);
        $lead->load(['website', 'assignedUser', 'notes.user', 'conversation.messages']);

        return view('dashboard.leads.show', compact('lead'));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->organization_id === auth()->user()->organization_id, 403);

        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', LeadStatus::pipeline()),
            'assigned_user_id' => 'nullable|exists:users,id',
            'follow_up_at' => 'nullable|date',
        ]);

        $lead->update($validated);

        return back()->with('success', 'Lead updated.');
    }

    public function storeNote(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->organization_id === auth()->user()->organization_id, 403);

        $validated = $request->validate(['body' => 'required|string|max:5000']);
        $lead->notes()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Note added.');
    }

    public function export(Request $request): StreamedResponse
    {
        $leads = Lead::query()
            ->where('organization_id', $request->user()->organization_id)
            ->with('website')
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($leads) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Company', 'Status', 'Website', 'Source', 'Created']);
            foreach ($leads as $lead) {
                fputcsv($out, [
                    $lead->id,
                    $lead->name,
                    $lead->email,
                    $lead->phone,
                    $lead->company,
                    $lead->status,
                    $lead->website?->name,
                    $lead->source_url,
                    $lead->created_at?->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, 'leads-'.now()->format('Y-m-d').'.csv');
    }
}
