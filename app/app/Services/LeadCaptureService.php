<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Website;
use Illuminate\Http\Request;

class LeadCaptureService
{
    public function captureFromConversation(Website $website, Conversation $conversation, ?Request $request = null): Lead
    {
        $transcript = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => sprintf('[%s] %s: %s', $m->created_at, $m->sender_type, $m->content))
            ->implode("\n");

        $lead = Lead::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
            ],
            [
                'organization_id' => $website->organization_id,
                'website_id' => $website->id,
                'name' => $conversation->visitor_name,
                'email' => $conversation->visitor_email,
                'phone' => $conversation->visitor_phone,
                'company' => $conversation->visitor_company,
                'website_url' => $website->url,
                'status' => LeadStatus::New->value,
                'source_url' => $conversation->source_url ?? $conversation->page_url,
                'ip_address' => $conversation->ip_address ?? $request?->ip(),
                'device_info' => $this->deviceInfo($request),
                'utm_params' => $conversation->utm_params,
                'chat_transcript' => $transcript,
            ]
        );

        if ($lead->wasRecentlyCreated) {
            app(WebhookDispatchService::class)->dispatch($website, 'lead.created', [
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'conversation_id' => $conversation->id,
            ]);
        }

        return $lead;
    }

    public function enrichConversation(Conversation $conversation, array $data, ?Request $request = null): Conversation
    {
        $conversation->fill([
            'visitor_name' => $data['visitor_name'] ?? $conversation->visitor_name,
            'visitor_email' => $data['visitor_email'] ?? $conversation->visitor_email,
            'visitor_phone' => $data['visitor_phone'] ?? $conversation->visitor_phone,
            'visitor_company' => $data['visitor_company'] ?? $conversation->visitor_company,
            'source_url' => $data['source_url'] ?? $data['page_url'] ?? $conversation->source_url,
            'page_url' => $data['page_url'] ?? $conversation->page_url,
            'ip_address' => $request?->ip() ?? $conversation->ip_address,
            'user_agent' => $request?->userAgent() ?? $conversation->user_agent,
            'utm_params' => $data['utm_params'] ?? $conversation->utm_params,
        ])->save();

        if ($conversation->visitor_email || $conversation->visitor_phone) {
            $this->captureFromConversation($conversation->website, $conversation, $request);
        }

        return $conversation;
    }

    protected function deviceInfo(?Request $request): ?array
    {
        if (! $request) {
            return null;
        }

        return [
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('Accept-Language'),
        ];
    }
}
