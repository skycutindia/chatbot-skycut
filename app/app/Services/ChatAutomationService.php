<?php

namespace App\Services;

use App\Models\ChatAutomationRule;
use App\Models\ChatEvent;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\Website;
use Illuminate\Support\Str;

class ChatAutomationService
{
    public function __construct(
        protected AgentAssignmentService $assignment,
        protected LeadCaptureService $leadCapture,
    ) {}

    public function applyForVisitorMessage(Message $message): void
    {
        if ($message->sender_type !== 'visitor') {
            return;
        }

        $message->loadMissing('conversation.website');
        $conversation = $message->conversation;
        $website = $conversation?->website;

        if (! $website) {
            return;
        }

        foreach ($this->activeRules($website, 'keyword') as $rule) {
            if ($this->matchesKeywords($rule, $message->content)) {
                $this->execute($rule, $conversation, $website);
            }
        }
    }

    public function applyForNewConversation(Conversation $conversation): void
    {
        $conversation->loadMissing('website');
        $website = $conversation->website;

        if (! $website) {
            return;
        }

        foreach ($this->activeRules($website, 'new_conversation') as $rule) {
            $this->execute($rule, $conversation, $website);
        }
    }

    public function closeInactiveConversations(): int
    {
        $closed = 0;

        $rules = ChatAutomationRule::query()
            ->where('trigger_type', 'inactive')
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $minutes = (int) ($rule->trigger_config['minutes'] ?? 60);
            if ($minutes < 5) {
                continue;
            }

            $cutoff = now()->subMinutes($minutes);

            $query = Conversation::query()
                ->whereIn('status', ['open', 'pending', 'waiting_visitor', 'awaiting_agent'])
                ->where('last_message_at', '<=', $cutoff)
                ->whereHas('website', function ($q) use ($rule) {
                    $q->where('organization_id', $rule->organization_id);
                    if ($rule->website_id) {
                        $q->where('id', $rule->website_id);
                    }
                });

            foreach ($query->cursor() as $conversation) {
                if ($rule->action_type === 'close') {
                    $conversation->update(['status' => 'closed', 'closed_at' => now()]);
                    ChatEvent::log($conversation, 'automation_closed', null, [
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                    ]);
                    $closed++;
                } else {
                    $this->execute($rule, $conversation, $conversation->website);
                }
            }
        }

        return $closed;
    }

    /** @return \Illuminate\Support\Collection<int, ChatAutomationRule> */
    protected function activeRules(Website $website, string $triggerType)
    {
        return ChatAutomationRule::query()
            ->where('organization_id', $website->organization_id)
            ->where('trigger_type', $triggerType)
            ->where('is_active', true)
            ->where(function ($q) use ($website) {
                $q->whereNull('website_id')->orWhere('website_id', $website->id);
            })
            ->orderByDesc('priority')
            ->get();
    }

    protected function matchesKeywords(ChatAutomationRule $rule, string $content): bool
    {
        $keywords = $rule->trigger_config['keywords'] ?? [];
        $normalized = Str::lower($content);

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && Str::contains($normalized, Str::lower($keyword))) {
                return true;
            }
        }

        return false;
    }

    protected function execute(ChatAutomationRule $rule, Conversation $conversation, Website $website): void
    {
        $config = $rule->action_config ?? [];

        match ($rule->action_type) {
            'assign_department' => $this->assignDepartment($conversation, (int) ($config['department_id'] ?? 0)),
            'assign_agent' => $this->assignAgent($conversation, $website, isset($config['department_id']) ? (int) $config['department_id'] : null),
            'add_tag' => $this->addTag($conversation, (string) ($config['tag'] ?? '')),
            'set_priority' => $this->setPriority($conversation, (string) ($config['priority'] ?? 'normal')),
            'close' => $this->closeConversation($conversation),
            'capture_lead' => $this->leadCapture->captureFromConversation($website, $conversation),
            'request_survey' => $this->addTag($conversation, 'csat_pending'),
            default => null,
        };

        ChatEvent::log($conversation, 'automation', null, [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'action' => $rule->action_type,
        ]);
    }

    protected function assignDepartment(Conversation $conversation, int $departmentId): void
    {
        if ($departmentId <= 0) {
            return;
        }

        $exists = Department::query()
            ->where('id', $departmentId)
            ->where('organization_id', $conversation->website->organization_id)
            ->exists();

        if ($exists) {
            $conversation->update(['department_id' => $departmentId]);
        }
    }

    protected function assignAgent(Conversation $conversation, Website $website, ?int $departmentId): void
    {
        if ($departmentId) {
            $this->assignDepartment($conversation, $departmentId);
        }

        $agent = $this->assignment->assignLeastBusy($website, $conversation->department_id);

        if ($agent) {
            $conversation->update([
                'assigned_user_id' => $agent->id,
                'status' => 'open',
                'mode' => 'human',
            ]);
        }
    }

    protected function addTag(Conversation $conversation, string $tag): void
    {
        $tag = trim($tag);
        if ($tag === '') {
            return;
        }

        $tags = $conversation->tags ?? [];
        if (! in_array($tag, $tags, true)) {
            $tags[] = $tag;
            $conversation->update(['tags' => $tags]);
        }
    }

    protected function setPriority(Conversation $conversation, string $priority): void
    {
        if (in_array($priority, ['low', 'normal', 'medium', 'high', 'urgent'], true)) {
            $conversation->update(['priority' => $priority]);
        }
    }

    protected function closeConversation(Conversation $conversation): void
    {
        if ($conversation->status !== 'closed') {
            $conversation->update(['status' => 'closed', 'closed_at' => now()]);
        }
    }
}
