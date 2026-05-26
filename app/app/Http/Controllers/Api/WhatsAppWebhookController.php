<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\WhatsappChannel;
use App\Services\ChatResponseService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request, string $organizationSlug, WhatsAppService $whatsapp, ChatResponseService $chatService): HttpResponse|Response
    {
        $organization = Organization::query()->where('slug', $organizationSlug)->firstOrFail();
        $channel = $this->channelForOrganization($organization);

        if (! $channel) {
            abort(404);
        }

        if ($request->isMethod('GET')) {
            $challenge = $whatsapp->verifyChallenge(
                $request->query('hub_mode'),
                $request->query('hub_verify_token'),
                $request->query('hub_challenge'),
                $channel
            );

            if ($challenge === null) {
                abort(403);
            }

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        $whatsapp->handleInboundPayload($request->all(), $chatService);

        return response()->noContent();
    }

    protected function channelForOrganization(Organization $organization): ?WhatsappChannel
    {
        return WhatsappChannel::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->first();
    }
}
