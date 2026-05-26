<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use App\Models\Website;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'super@aichatbothub.local'],
            [
                'organization_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::SuperAdmin->value,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $org = Organization::updateOrCreate(
            ['slug' => 'demo-company'],
            [
                'name' => 'Demo Company',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@aichatbothub.local'],
            [
                'organization_id' => $org->id,
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => UserRole::Owner->value,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'agent@aichatbothub.local'],
            [
                'organization_id' => $org->id,
                'name' => 'Support Agent',
                'password' => Hash::make('password'),
                'role' => UserRole::Agent->value,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $website = Website::updateOrCreate(
            ['organization_id' => $org->id, 'demo_slug' => 'skycut'],
            [
                'name' => 'SkyCut',
                'url' => rtrim(config('app.url'), '/').'/demo/skycut',
                'domain' => parse_url(config('app.url'), PHP_URL_HOST) ?: '127.0.0.1',
                'language' => 'en',
                'is_active' => true,
            ]
        );

        $website->allowedDomains()->firstOrCreate(['domain' => 'localhost']);
        $website->allowedDomains()->firstOrCreate(['domain' => '127.0.0.1']);

        foreach ($website->operatingHours as $hour) {
            $hour->update([
                'opens_at' => '09:00:00',
                'closes_at' => '18:00:00',
                'is_closed' => in_array((int) $hour->day_of_week, [0, 6], true),
                'timezone' => config('app.timezone', 'UTC'),
            ]);
        }

        $website->configuration->update([
            'bot_name' => 'SkyCut Assistant',
            'welcome_message' => 'Hi! I\'m SkyCut Assistant. Ask about pricing, features, or say "agent" to talk to our team.',
            'primary_color' => '#0d9488',
            'secondary_color' => '#14b8a6',
            'theme_mode' => 'light',
            'max_tokens' => 1024,
            'handoff_triggers' => config('chatbot.default_handoff_triggers'),
        ]);

        if ($website->suggestedQuestions()->count() === 0) {
            $website->suggestedQuestions()->createMany([
                ['question' => 'What are your pricing plans?', 'sort_order' => 0],
                ['question' => 'How does live agent handoff work?', 'sort_order' => 1],
                ['question' => 'I need help with my subscription', 'sort_order' => 2],
            ]);
        }

        if ($website->quickActions()->count() === 0) {
            $website->quickActions()->createMany([
                ['label' => 'View pricing', 'icon' => '💰', 'action_type' => 'url', 'action_value' => '#pricing', 'sort_order' => 0],
                ['label' => 'Talk to agent', 'icon' => '💬', 'action_type' => 'message', 'action_value' => 'I need to speak with a live agent', 'sort_order' => 1],
                ['label' => 'Book a demo', 'icon' => '📅', 'action_type' => 'url', 'action_value' => '#features', 'sort_order' => 2],
            ]);
        }

        if ($website->qaPairs()->count() === 0) {
            $website->qaPairs()->createMany([
                [
                    'question' => 'pricing',
                    'answer' => 'We offer Free, Pro ($49/mo), and Enterprise plans. Visit our pricing section or ask for a custom quote.',
                    'trigger_keywords' => ['price', 'cost', 'pricing', 'subscription', 'plan'],
                    'question_variations' => ['how much does it cost', 'what are your rates'],
                    'priority' => 10,
                    'is_active' => true,
                    'is_published' => true,
                ],
                [
                    'question' => 'business hours',
                    'answer' => 'SkyCut support is available Monday–Friday, 9:00 AM–6:00 PM (app timezone). Weekend messages are answered on the next business day.',
                    'trigger_keywords' => ['hours', 'open', 'closed', 'weekend', 'schedule'],
                    'question_variations' => ['when are you open', 'what are your hours'],
                    'priority' => 8,
                    'is_active' => true,
                    'is_published' => true,
                ],
                [
                    'question' => 'live agent',
                    'answer' => 'Type "agent" or "human" anytime to connect with our team. You can also use the Talk to agent quick action.',
                    'trigger_keywords' => ['agent', 'human', 'representative', 'support'],
                    'question_variations' => ['talk to a person', 'speak with support'],
                    'priority' => 9,
                    'is_active' => true,
                    'is_published' => true,
                ],
            ]);
        }

        $cat = $website->knowledgeCategories()->firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General']
        );

        $website->knowledgeArticles()->firstOrCreate(
            ['slug' => 'about'],
            [
                'knowledge_category_id' => $cat->id,
                'title' => 'About AI Chatbot Hub Pro',
                'content' => 'AI Chatbot Hub Pro is a multi-tenant SaaS platform for deploying AI chatbots across unlimited websites with live agent handoff, lead capture, and analytics.',
                'is_published' => true,
            ]
        );

        $this->command?->info('Super Admin: super@aichatbothub.local / password');
        $this->command?->info('Owner: admin@aichatbothub.local / password');
        $this->command?->info('Agent: agent@aichatbothub.local / password');
        $this->command?->info('Demo site: '.route('demo.show', 'skycut'));
        $this->command?->info('Admin chatbot hub: '.route('websites.hub', $website));
        $this->command?->info('Bot token: '.$website->bot_token);
    }
}
