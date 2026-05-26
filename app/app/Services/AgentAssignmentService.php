<?php

namespace App\Services;

use App\Models\ChatEvent;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\User;
use App\Models\Website;
use Illuminate\Support\Collection;

class AgentAssignmentService
{
    public function assignLeastBusy(Website $website, ?int $departmentId = null): ?User
    {
        $agents = $this->availableAgents($website->organization_id, $departmentId);

        if ($agents->isEmpty()) {
            return null;
        }

        return $agents
            ->sortBy(fn (User $user) => $this->openChatCount($user))
            ->first();
    }

    public function availableAgents(int $organizationId, ?int $departmentId = null): Collection
    {
        if ($departmentId) {
            $department = Department::query()
                ->where('id', $departmentId)
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->withCount('agents')
                ->first();

            if (! $department || $department->agents_count === 0) {
                return collect();
            }
        }

        $query = User::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->whereIn('role', ['agent', 'manager', 'admin', 'owner'])
            ->with('agentStatus');

        if ($departmentId) {
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $departmentId));
        }

        return $query
            ->get()
            ->filter(function (User $user) {
                $status = $user->agentStatus;

                if (! $status) {
                    return true;
                }

                if (! $status->acceptsChats()) {
                    return false;
                }

                return $this->openChatCount($user) < $status->max_concurrent_chats;
            });
    }

    public function openChatCount(User $user): int
    {
        return Conversation::query()
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', ['open', 'awaiting_agent', 'pending', 'waiting_visitor'])
            ->count();
    }

    public function transfer(Conversation $conversation, User $agent, User $by): Conversation
    {
        $conversation->update([
            'assigned_user_id' => $agent->id,
            'status' => 'open',
            'mode' => 'human',
        ]);

        ChatEvent::log($conversation, 'transferred', $by, [
            'to_user_id' => $agent->id,
            'to_user_name' => $agent->name,
        ]);

        return $conversation->fresh();
    }
}
