<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\NotificationLog;
use App\Models\Organization;
use Illuminate\Support\Facades\Http;

class ChatIntegrationNotificationService
{
    public function notifyHandoff(Conversation $conversation, string $reason = 'handoff'): void
    {
        $conversation->loadMissing(['website.organization', 'assignedUser', 'department']);
        $organization = $conversation->website?->organization;

        if (! $organization) {
            return;
        }

        $settings = $organization->settings ?? [];
        $title = 'Live chat handoff';
        $body = $this->handoffBody($conversation, $reason);
        $url = $this->inboxUrl($conversation);

        if ($this->slackEnabled($settings, 'handoff')) {
            $this->sendSlack($organization, $conversation, 'handoff', $settings['slack_webhook_url'], $title, $body, $url);
        }

        if ($this->teamsEnabled($settings, 'handoff')) {
            $this->sendTeams($organization, $conversation, 'handoff', $settings['teams_webhook_url'], $title, $body, $url);
        }
    }

    public function notifyNewVisitorMessage(Conversation $conversation, Message $message): void
    {
        if ($conversation->status !== 'awaiting_agent') {
            return;
        }

        $conversation->loadMissing(['website.organization']);
        $organization = $conversation->website?->organization;

        if (! $organization) {
            return;
        }

        $settings = $organization->settings ?? [];
        $title = 'New visitor message (queue)';
        $body = sprintf(
            "*%s* on %s\n> %s",
            $conversation->visitor_name ?: 'Visitor',
            $conversation->website?->name ?? 'Website',
            \Illuminate\Support\Str::limit($message->content, 240)
        );
        $url = $this->inboxUrl($conversation);

        if ($this->slackEnabled($settings, 'new_message')) {
            $this->sendSlack($organization, $conversation, 'new_message', $settings['slack_webhook_url'], $title, $body, $url);
        }

        if ($this->teamsEnabled($settings, 'new_message')) {
            $this->sendTeams($organization, $conversation, 'new_message', $settings['teams_webhook_url'], $title, $body, $url);
        }
    }

    protected function handoffBody(Conversation $conversation, string $reason): string
    {
        $visitor = $conversation->visitor_name ?: 'Visitor';
        $website = $conversation->website?->name ?? 'Website';
        $department = $conversation->department?->name;
        $assigned = $conversation->assignedUser?->name;

        $lines = [
            "*{$visitor}* needs a human agent on *{$website}*",
            'Reason: '.str_replace('_', ' ', $reason),
        ];

        if ($department) {
            $lines[] = 'Department: '.$department;
        }

        if ($assigned) {
            $lines[] = 'Assigned: '.$assigned;
        }

        return implode("\n", $lines);
    }

    protected function inboxUrl(Conversation $conversation): string
    {
        return url(route('inbox.index', ['conversation' => $conversation->id], false));
    }

    /** @param array<string, mixed> $settings */
    protected function slackEnabled(array $settings, string $event): bool
    {
        if (empty($settings['slack_webhook_url'])) {
            return false;
        }

        return match ($event) {
            'handoff' => (bool) ($settings['notify_slack_handoff'] ?? true),
            'new_message' => (bool) ($settings['notify_slack_new_message'] ?? false),
            default => false,
        };
    }

    /** @param array<string, mixed> $settings */
    protected function teamsEnabled(array $settings, string $event): bool
    {
        if (empty($settings['teams_webhook_url'])) {
            return false;
        }

        return match ($event) {
            'handoff' => (bool) ($settings['notify_teams_handoff'] ?? true),
            'new_message' => (bool) ($settings['notify_teams_new_message'] ?? false),
            default => false,
        };
    }

    protected function sendSlack(
        Organization $organization,
        Conversation $conversation,
        string $eventType,
        string $webhookUrl,
        string $title,
        string $body,
        string $actionUrl,
    ): void {
        $payload = [
            'text' => $title,
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => ['type' => 'mrkdwn', 'text' => $body],
                ],
                [
                    'type' => 'actions',
                    'elements' => [[
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => 'Open in inbox'],
                        'url' => $actionUrl,
                    ]],
                ],
            ],
        ];

        $this->postWebhook($organization, $conversation, 'slack', $eventType, $webhookUrl, $payload);
    }

    protected function sendTeams(
        Organization $organization,
        Conversation $conversation,
        string $eventType,
        string $webhookUrl,
        string $title,
        string $body,
        string $actionUrl,
    ): void {
        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'summary' => $title,
            'themeColor' => '0076D7',
            'title' => $title,
            'text' => str_replace(['*', '> '], '', $body),
            'potentialAction' => [[
                '@type' => 'OpenUri',
                'name' => 'Open in inbox',
                'targets' => [['os' => 'default', 'uri' => $actionUrl]],
            ]],
        ];

        $this->postWebhook($organization, $conversation, 'teams', $eventType, $webhookUrl, $payload);
    }

    /** @param array<string, mixed> $payload */
    protected function postWebhook(
        Organization $organization,
        Conversation $conversation,
        string $channel,
        string $eventType,
        string $webhookUrl,
        array $payload,
    ): void {
        try {
            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if ($response->successful() || $response->status() === 204) {
                NotificationLog::record(
                    $organization->id,
                    $channel,
                    $eventType,
                    'sent',
                    $conversation->id,
                    ['status_code' => $response->status()]
                );

                return;
            }

            NotificationLog::record(
                $organization->id,
                $channel,
                $eventType,
                'failed',
                $conversation->id,
                ['status_code' => $response->status()],
                $response->body()
            );
        } catch (\Throwable $e) {
            NotificationLog::record(
                $organization->id,
                $channel,
                $eventType,
                'failed',
                $conversation->id,
                null,
                $e->getMessage()
            );
        }
    }
}
