<?php

namespace App\Providers;

use App\Models\Message;
use App\Observers\MessageObserver;
use App\Events\AgentMentionedInNote;
use App\Events\ConversationAwaitingAgent;
use App\Listeners\SendAgentMentionNotification;
use App\Listeners\SendChatIntegrationNotifications;
use App\Listeners\SendHandoffNotification;
use App\View\Composers\DashboardComposer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use App\Services\AiConfigService;
use App\Support\HttpSsl;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Http::globalOptions(HttpSsl::clientOptions());

        if (Schema::hasTable('platform_settings')) {
            $this->app->make(AiConfigService::class)->ensurePlatformKeyFromEnvironment();
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Message::observe(MessageObserver::class);

        Event::listen(Registered::class, SendEmailVerificationNotification::class);
        Event::listen(ConversationAwaitingAgent::class, SendHandoffNotification::class);
        Event::listen(ConversationAwaitingAgent::class, SendChatIntegrationNotifications::class);
        Event::listen(AgentMentionedInNote::class, SendAgentMentionNotification::class);

        View::composer(['layouts.app', 'dashboard.*'], DashboardComposer::class);
    }
}
