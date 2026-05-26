<?php

namespace App\Services;

use App\Events\ConversationAwaitingAgent;
use App\Events\ConversationUpdated;
use App\Models\ChatEvent;
use App\Models\Conversation;
use App\Models\EscalationRule;
use App\Models\OperatingHour;
use App\Models\Website;
use Illuminate\Support\Str;

class LiveHandoffService
{
    public function __construct(
        protected LeadCaptureService $leadCapture,
        protected AgentAssignmentService $assignment,
    ) {}

    public function shouldHandoff(Website $website, Conversation $conversation, string $userMessage, float $confidence, string $source): bool
    {
        $config = $website->configuration;
        $triggers = $config->handoff_triggers ?? config('chatbot.default_handoff_triggers', []);
        $normalized = Str::lower($userMessage);

        foreach ($triggers['keywords'] ?? ['human', 'agent', 'speak to someone', 'live chat', 'talk to human', 'support agent', 'real person', 'representative'] as $keyword) {
            if (Str::contains($normalized, Str::lower($keyword))) {
                return true;
            }
        }

        if ($this->matchesEscalationRules($website, $userMessage, $confidence)) {
            return true;
        }

        if ($source === 'ai' && $confidence < (float) $config->confidence_threshold) {
            $streak = (int) $conversation->low_confidence_streak + 1;
            $conversation->update(['low_confidence_streak' => $streak]);

            return $streak >= (int) config('chatbot.handoff_low_confidence_streak', 2);
        }

        return false;
    }

    public function canHandoffNow(Website $website): bool
    {
        if (! OperatingHour::isWithinHours($website)) {
            return (bool) ($website->configuration->handoff_triggers['allow_outside_hours'] ?? false);
        }

        return true;
    }

    public function initiate(Website $website, Conversation $conversation, string $reason = 'low_confidence'): Conversation
    {
        if (! $this->canHandoffNow($website)) {
            return $conversation;
        }

        $conversation->update([
            'status' => 'awaiting_agent',
            'mode' => 'human',
            'sla_due_at' => now()->addMinutes(config('chatbot.sla_minutes', 15)),
            'agent_unread_count' => ($conversation->agent_unread_count ?? 0) + 1,
        ]);

        $lead = $this->leadCapture->captureFromConversation($website, $conversation);

        $agent = $this->assignment->assignLeastBusy($website, $conversation->department_id);
        if ($agent) {
            $conversation->update(['assigned_user_id' => $agent->id]);
            $lead->update(['assigned_user_id' => $agent->id]);
        }

        ChatEvent::log($conversation, 'handoff', null, ['reason' => $reason]);

        event(new ConversationAwaitingAgent($conversation, $reason));
        broadcast(new ConversationUpdated($conversation->fresh(), 'handoff'));

        return $conversation->fresh();
    }

    public function returnToAi(Conversation $conversation): Conversation
    {
        $conversation->update([
            'status' => 'open',
            'mode' => 'ai',
            'assigned_user_id' => null,
            'low_confidence_streak' => 0,
        ]);

        ChatEvent::log($conversation, 'return_to_ai', auth()->user());

        broadcast(new ConversationUpdated($conversation->fresh(), 'return_to_ai'));

        return $conversation->fresh();
    }

    public function requestHuman(Website $website, Conversation $conversation): Conversation
    {
        return $this->initiate($website, $conversation, 'visitor_request');
    }

    protected function matchesEscalationRules(Website $website, string $message, float $confidence): bool
    {
        $rules = EscalationRule::query()
            ->where('website_id', $website->id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($rule->trigger_type === 'keyword') {
                $keywords = $rule->trigger_config['keywords'] ?? [];
                foreach ($keywords as $keyword) {
                    if (Str::contains(Str::lower($message), Str::lower($keyword))) {
                        return true;
                    }
                }
            }

            if ($rule->trigger_type === 'confidence' && $confidence < (float) ($rule->trigger_config['threshold'] ?? 0.5)) {
                return true;
            }
        }

        return false;
    }
}
