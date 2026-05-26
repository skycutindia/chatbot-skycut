<?php

use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Dashboard\AgentInboxController;
use App\Http\Controllers\Dashboard\AgentQuickReplyController;
use App\Http\Controllers\Dashboard\AgentMentionController;
use App\Http\Controllers\Dashboard\AgentPresenceController;
use App\Http\Controllers\Dashboard\AgentPushController;
use App\Http\Controllers\Dashboard\AgentPwaController;
use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\ChatAttachmentController;
use App\Http\Controllers\Dashboard\ChatTranscriptController;
use App\Http\Controllers\Dashboard\LiveChatAnalyticsController;
use App\Http\Controllers\Dashboard\AutomationRuleController;
use App\Http\Controllers\Dashboard\CannedResponseController;
use App\Http\Controllers\Dashboard\DepartmentController;
use App\Http\Controllers\Dashboard\ChatbotHubController;
use App\Http\Controllers\Dashboard\ConversationController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\DashboardSettingsController;
use App\Http\Controllers\Dashboard\KnowledgeController;
use App\Http\Controllers\Dashboard\KnowledgeTrainingController;
use App\Http\Controllers\Dashboard\LeadController;
use App\Http\Controllers\Dashboard\InboxFilterPresetController;
use App\Http\Controllers\Dashboard\OrganizationInviteController;
use App\Http\Controllers\Dashboard\OrganizationSettingsController;
use App\Http\Controllers\TeamInviteAcceptController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\QaPairController;
use App\Http\Controllers\Dashboard\QuickActionController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\Dashboard\SearchController;
use App\Http\Controllers\Dashboard\SuggestedQuestionController;
use App\Http\Controllers\Dashboard\TeamController;
use App\Http\Controllers\Dashboard\TwoFactorController;
use App\Http\Controllers\Dashboard\WebsiteController;
use App\Http\Controllers\Dashboard\WebsiteQuestionController;
use App\Http\Controllers\Dashboard\WebsiteTrainingHubController;
use App\Http\Controllers\Dashboard\WhatsAppSettingsController;
use App\Http\Controllers\Dashboard\WidgetEmbedController;
use App\Http\Controllers\ExampleWebsiteController;
use App\Http\Middleware\EnsureOrganizationAccess;
use App\Http\Middleware\EnsureRole;
use App\Models\Website;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/demo/{slug}', [ExampleWebsiteController::class, 'show'])->name('demo.show');
Route::get('/demo/{slug}/{page}', [ExampleWebsiteController::class, 'page'])
    ->where('page', 'features|pricing|chatbot|contact')
    ->name('demo.page');

Route::get('/agent/manifest.webmanifest', [AgentPwaController::class, 'manifest'])->name('agent.pwa.manifest');
Route::get('/agent/icon-{size}.svg', [AgentPwaController::class, 'icon'])->whereNumber('size')->name('agent.pwa.icon');

Route::get('/team/invite/{token}', [TeamInviteAcceptController::class, 'show'])->name('team.invite.show');
Route::post('/team/invite/{token}', [TeamInviteAcceptController::class, 'accept'])->name('team.invite.accept');

Route::get('/', function () {
    $slug = config('chatbot.demo_website_slug');

    if ($slug && Schema::hasTable('websites') && Website::query()->where('demo_slug', $slug)->where('is_active', true)->exists()) {
        return redirect()->route('demo.show', $slug);
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
});

Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.login');
Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('two-factor.verify');

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout-beacon', [LoginController::class, 'beaconLogout'])->name('auth.logout-beacon');
    Route::get('/auth/session-expired', [LoginController::class, 'sessionExpired'])->name('auth.session-expired');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified', 'dashboard.auth'])->group(function () {
    Route::get('/settings/two-factor', [TwoFactorController::class, 'show'])->name('settings.two-factor.show');
    Route::post('/settings/two-factor/enable', [TwoFactorController::class, 'enable'])->name('settings.two-factor.enable');
    Route::post('/settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('settings.two-factor.confirm');
    Route::get('/settings/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('settings.two-factor.recovery-codes');
    Route::post('/settings/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('settings.two-factor.recovery-codes.regenerate');
    Route::post('/settings/two-factor/cancel', [TwoFactorController::class, 'cancelSetup'])->name('settings.two-factor.cancel');
    Route::delete('/settings/two-factor', [TwoFactorController::class, 'disable'])->name('settings.two-factor.disable');
});

Route::middleware(['auth', 'verified', 'dashboard.auth', EnsureRole::class.':super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::patch('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
    Route::get('/settings', [PlatformSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [PlatformSettingsController::class, 'update'])->name('settings.update');
});

Route::middleware(['auth', 'verified', 'dashboard.auth', EnsureOrganizationAccess::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/search', SearchController::class)->name('search');

    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');

    Route::middleware(EnsureRole::class.':super_admin,owner,admin')->group(function () {
        Route::get('/settings', [DashboardSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/ai', [DashboardSettingsController::class, 'updateAi'])->name('settings.ai.update');
        Route::put('/settings/platform-ai', [DashboardSettingsController::class, 'updatePlatformAi'])->name('settings.platform-ai.update');
        Route::put('/settings/dashboard-modules', [DashboardSettingsController::class, 'updateDashboardModules'])->name('settings.dashboard-modules.update');
        Route::post('/settings/ai/test', [DashboardSettingsController::class, 'testAi'])->name('settings.ai.test');
        Route::get('/settings/organization', [OrganizationSettingsController::class, 'edit'])->name('settings.organization.edit');
        Route::put('/settings/organization', [OrganizationSettingsController::class, 'update'])->name('settings.organization.update');
        Route::post('/settings/organization/invites', [OrganizationInviteController::class, 'store'])->name('settings.organization.invites.store');
        Route::delete('/settings/organization/invites/{invite}', [OrganizationInviteController::class, 'destroy'])->name('settings.organization.invites.destroy');
        Route::get('/settings/whatsapp', [WhatsAppSettingsController::class, 'edit'])->name('settings.whatsapp.edit');
        Route::put('/settings/whatsapp', [WhatsAppSettingsController::class, 'update'])->name('settings.whatsapp.update');
    });

    Route::middleware(EnsureRole::class.':super_admin,owner,admin')->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::get('/team/create', [TeamController::class, 'create'])->name('team.create');
        Route::post('/team', [TeamController::class, 'store'])->name('team.store');
        Route::get('/team/{user}/edit', [TeamController::class, 'edit'])->name('team.edit');
        Route::patch('/team/{user}', [TeamController::class, 'update'])->name('team.update');
        Route::delete('/team/{user}', [TeamController::class, 'destroy'])->name('team.destroy');
    });

    Route::middleware(EnsureRole::class.':super_admin,owner,admin,manager')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::put('/departments/{department}/agents', [DepartmentController::class, 'syncAgents'])->name('departments.agents.sync');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
        Route::get('/automation-rules', [AutomationRuleController::class, 'index'])->name('automation-rules.index');
        Route::post('/automation-rules', [AutomationRuleController::class, 'store'])->name('automation-rules.store');
        Route::put('/automation-rules/{automationRule}', [AutomationRuleController::class, 'update'])->name('automation-rules.update');
        Route::delete('/automation-rules/{automationRule}', [AutomationRuleController::class, 'destroy'])->name('automation-rules.destroy');
    });

    Route::middleware(EnsureRole::class.':super_admin,owner,admin,manager,agent')->group(function () {
        Route::get('/canned-responses', [CannedResponseController::class, 'index'])->name('canned-responses.index');
        Route::post('/canned-responses', [CannedResponseController::class, 'store'])->name('canned-responses.store');
        Route::delete('/canned-responses/{cannedResponse}', [CannedResponseController::class, 'destroy'])->name('canned-responses.destroy');
    });

    Route::middleware(EnsureRole::class.':super_admin,owner,admin,manager,agent,viewer')->group(function () {
        Route::get('/inbox', [AgentInboxController::class, 'index'])->name('inbox.index');
        Route::get('/inbox/filter-presets', [InboxFilterPresetController::class, 'index'])->name('inbox.filter-presets.index');
        Route::post('/inbox/filter-presets', [InboxFilterPresetController::class, 'store'])->name('inbox.filter-presets.store');
        Route::delete('/inbox/filter-presets/{preset}', [InboxFilterPresetController::class, 'destroy'])->name('inbox.filter-presets.destroy');
        Route::get('/inbox/export', [AgentInboxController::class, 'export'])->name('inbox.export');
        Route::get('/inbox/archive', [AgentInboxController::class, 'archive'])->name('inbox.archive');
        Route::get('/inbox/queue', [AgentInboxController::class, 'queue'])->name('inbox.queue');
        Route::get('/inbox/poll', [AgentInboxController::class, 'poll'])->name('inbox.poll');
        Route::get('/inbox/mentions/search', [AgentMentionController::class, 'search'])->name('inbox.mentions.search');
        Route::get('/inbox/mentions', [AgentMentionController::class, 'index'])->name('inbox.mentions.index');
        Route::post('/inbox/mentions/read', [AgentMentionController::class, 'markRead'])->name('inbox.mentions.read');
        Route::post('/inbox/push/subscribe', [AgentPushController::class, 'subscribe'])->name('inbox.push.subscribe');
        Route::post('/inbox/push/unsubscribe', [AgentPushController::class, 'unsubscribe'])->name('inbox.push.unsubscribe');
        Route::post('/inbox/bulk', [AgentInboxController::class, 'bulk'])->name('inbox.bulk');
        Route::post('/inbox/quick-replies', [AgentQuickReplyController::class, 'store'])->name('inbox.quick-replies.store');
        Route::put('/inbox/quick-replies/{agentQuickReply}', [AgentQuickReplyController::class, 'update'])->name('inbox.quick-replies.update');
        Route::delete('/inbox/quick-replies/{agentQuickReply}', [AgentQuickReplyController::class, 'destroy'])->name('inbox.quick-replies.destroy');
        Route::post('/inbox/presence', [AgentPresenceController::class, 'update'])->name('inbox.presence');
        Route::post('/inbox/{conversation}/assign', [AgentInboxController::class, 'assign'])->name('inbox.assign');
        Route::post('/inbox/{conversation}/transfer', [AgentInboxController::class, 'transfer'])->name('inbox.transfer');
        Route::post('/inbox/{conversation}/meta', [AgentInboxController::class, 'updateMeta'])->name('inbox.meta');
        Route::post('/inbox/{conversation}/notes', [AgentInboxController::class, 'storeNote'])->name('inbox.notes.store');
        Route::post('/inbox/{conversation}/save-lead', [AgentInboxController::class, 'saveAsLead'])->name('inbox.save-lead');
        Route::post('/inbox/{conversation}/contact', [AgentInboxController::class, 'updateContact'])->name('inbox.contact');
        Route::post('/inbox/{conversation}/close', [AgentInboxController::class, 'close'])->name('inbox.close');
        Route::post('/inbox/{conversation}/reopen', [AgentInboxController::class, 'reopen'])->name('inbox.reopen');
        Route::post('/inbox/{conversation}/resolve', [AgentInboxController::class, 'resolve'])->name('inbox.resolve');
        Route::post('/inbox/{conversation}/return-to-ai', [AgentInboxController::class, 'returnToAi'])->name('inbox.return-to-ai');
        Route::post('/inbox/{conversation}/reply', [ConversationController::class, 'replyFromInbox'])->name('inbox.reply');
        Route::get('/inbox/{conversation}/transcript.csv', [ChatTranscriptController::class, 'csv'])->name('inbox.transcript.csv');
        Route::get('/inbox/{conversation}/transcript.txt', [ChatTranscriptController::class, 'txt'])->name('inbox.transcript.txt');
        Route::get('/inbox/{conversation}/transcript.pdf', [ChatTranscriptController::class, 'pdf'])->name('inbox.transcript.pdf');
        Route::post('/inbox/{conversation}/attachments', [ChatAttachmentController::class, 'upload'])->name('inbox.attachments.upload');
        Route::get('/live-chat/analytics', [LiveChatAnalyticsController::class, 'index'])->name('live-chat.analytics');
        Route::get('/live-chat/analytics/export', [LiveChatAnalyticsController::class, 'export'])->name('live-chat.analytics.export');
        Route::get('/chat/attachments/{attachment}', [ChatAttachmentController::class, 'download'])->name('chat.attachments.download');
    });

    Route::middleware(EnsureRole::class.':super_admin,owner,admin,manager,viewer')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::middleware(EnsureRole::class.':super_admin,owner,admin,manager,viewer')->group(function () {
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::patch('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::post('/leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('leads.notes.store');
    });

    Route::middleware(EnsureRole::class.':super_admin,owner,admin,manager,agent,viewer')->group(function () {
        Route::get('websites/{website}/hub', [ChatbotHubController::class, 'show'])->name('websites.hub');
    });

    Route::middleware(EnsureRole::class.':super_admin,owner,admin,manager')->group(function () {
        Route::get('websites/create/bot', [WebsiteController::class, 'createBot'])->name('websites.create.bot');
        Route::post('websites/step-1', [WebsiteController::class, 'storeStep1'])->name('websites.store.step1');
        Route::put('websites/{website}/settings/website', [WebsiteController::class, 'updateWebsite'])->name('websites.update.website');
        Route::get('websites/{website}/settings/bot', [WebsiteController::class, 'editBot'])->name('websites.edit.bot');
        Route::put('websites/{website}/settings/bot', [WebsiteController::class, 'updateBot'])->name('websites.update.bot');
        Route::get('websites/{website}/settings/advanced', [WebsiteController::class, 'advanced'])->name('websites.advanced');
        Route::get('websites/{website}/training', [WebsiteTrainingHubController::class, 'index'])->name('websites.training.index');
        Route::post('websites/{website}/training/generate-qa', [WebsiteTrainingHubController::class, 'generateQaFromUrl'])->name('websites.training.generate-qa');
        Route::post('websites/{website}/training/approve-qa', [WebsiteTrainingHubController::class, 'approveQaDraft'])->name('websites.training.approve-qa');
        Route::post('websites/{website}/training/discard-qa', [WebsiteTrainingHubController::class, 'discardQaDraft'])->name('websites.training.discard-qa');
        Route::post('websites/{website}/training/crawl', [WebsiteTrainingHubController::class, 'crawl'])->name('websites.training.crawl');
        Route::post('websites/{website}/training/keywords', [WebsiteTrainingHubController::class, 'storeKeyword'])->name('websites.training.keywords.store');
        Route::delete('websites/{website}/training/keywords/{triggerKeyword}', [WebsiteTrainingHubController::class, 'destroyKeyword'])->name('websites.training.keywords.destroy');
        Route::get('websites/{website}/questions', [WebsiteQuestionController::class, 'index'])->name('websites.questions.index');
        Route::post('websites/{website}/questions', [WebsiteQuestionController::class, 'store'])->name('websites.questions.store');
        Route::put('websites/{website}/questions/{qaPair}', [WebsiteQuestionController::class, 'update'])->name('websites.questions.update');
        Route::post('websites/{website}/questions/{qaPair}/clone', [WebsiteQuestionController::class, 'clone'])->name('websites.questions.clone');
        Route::delete('websites/{website}/questions/{qaPair}', [WebsiteQuestionController::class, 'destroy'])->name('websites.questions.destroy');
        Route::post('websites/{website}/questions/bulk-delete', [WebsiteQuestionController::class, 'bulkDestroy'])->name('websites.questions.bulk-delete');
        Route::post('websites/{website}/questions/import', [WebsiteQuestionController::class, 'import'])->name('websites.questions.import');
        Route::resource('websites', WebsiteController::class);
        Route::get('websites/{website}/analytics', [AnalyticsController::class, 'show'])->name('websites.analytics');
        Route::get('websites/{website}/analytics/export', [AnalyticsController::class, 'export'])->name('websites.analytics.export');
        Route::get('websites/{website}/embed', [WidgetEmbedController::class, 'show'])->name('websites.embed');
        Route::get('websites/{website}/embed/download', [WidgetEmbedController::class, 'download'])->name('websites.embed.download');
        Route::post('websites/{website}/embed/regenerate', [WidgetEmbedController::class, 'regenerate'])->name('websites.embed.regenerate');
        Route::get('websites/{website}/embed/install-guide', [WidgetEmbedController::class, 'installGuide'])->name('websites.embed.install-guide');
        Route::get('websites/{website}/embed/readme', [WidgetEmbedController::class, 'downloadReadme'])->name('websites.embed.readme');
        Route::post('websites/{website}/duplicate', [WebsiteController::class, 'duplicate'])->name('websites.duplicate');
        Route::post('websites/{website}/toggle-status', [WebsiteController::class, 'toggleStatus'])->name('websites.toggle-status');
        Route::get('websites/{website}/unanswered', [\App\Http\Controllers\Dashboard\UnansweredQuestionController::class, 'index'])->name('websites.unanswered.index');
        Route::post('websites/{website}/unanswered/{unanswered}/resolve', [\App\Http\Controllers\Dashboard\UnansweredQuestionController::class, 'resolve'])->name('websites.unanswered.resolve');
        Route::post('websites/{website}/unanswered/{unanswered}/dismiss', [\App\Http\Controllers\Dashboard\UnansweredQuestionController::class, 'dismiss'])->name('websites.unanswered.dismiss');
        Route::post('websites/{website}/unanswered/bulk', [\App\Http\Controllers\Dashboard\UnansweredQuestionController::class, 'bulk'])->name('websites.unanswered.bulk');
        Route::get('websites/{website}/keywords', [\App\Http\Controllers\Dashboard\TriggerKeywordController::class, 'index'])->name('websites.keywords.index');
        Route::post('websites/{website}/keywords', [\App\Http\Controllers\Dashboard\TriggerKeywordController::class, 'store'])->name('websites.keywords.store');
        Route::delete('websites/{website}/keywords/{triggerKeyword}', [\App\Http\Controllers\Dashboard\TriggerKeywordController::class, 'destroy'])->name('websites.keywords.destroy');
        Route::get('websites/{website}/webhooks', [\App\Http\Controllers\Dashboard\WebhookController::class, 'index'])->name('websites.webhooks.index');
        Route::post('websites/{website}/webhooks', [\App\Http\Controllers\Dashboard\WebhookController::class, 'store'])->name('websites.webhooks.store');
        Route::delete('websites/{website}/webhooks/{webhook}', [\App\Http\Controllers\Dashboard\WebhookController::class, 'destroy'])->name('websites.webhooks.destroy');

        Route::get('websites/{website}/knowledge', [KnowledgeController::class, 'index'])->name('websites.knowledge.index');
        Route::get('websites/{website}/knowledge/training', [KnowledgeTrainingController::class, 'index'])->name('websites.knowledge.training');
        Route::post('websites/{website}/knowledge/crawl', [KnowledgeTrainingController::class, 'crawl'])->name('websites.knowledge.crawl');
        Route::post('websites/{website}/knowledge/upload', [KnowledgeTrainingController::class, 'upload'])->name('websites.knowledge.upload');
        Route::delete('websites/{website}/knowledge/sources/{source}', [KnowledgeTrainingController::class, 'destroy'])->name('websites.knowledge.sources.destroy');
        Route::post('websites/{website}/knowledge/articles', [KnowledgeController::class, 'storeArticle'])->name('websites.knowledge.articles.store');
        Route::put('websites/{website}/knowledge/articles/{article}', [KnowledgeController::class, 'updateArticle'])->name('websites.knowledge.articles.update');
        Route::post('websites/{website}/knowledge/categories', [KnowledgeController::class, 'storeCategory'])->name('websites.knowledge.categories.store');
        Route::delete('websites/{website}/knowledge/articles/{article}', [KnowledgeController::class, 'destroyArticle'])->name('websites.knowledge.articles.destroy');
        Route::post('websites/{website}/knowledge/import', [KnowledgeController::class, 'importFaq'])->name('websites.knowledge.import');

        Route::get('websites/{website}/quick-actions', [QuickActionController::class, 'index'])->name('websites.quick-actions.index');
        Route::post('websites/{website}/quick-actions', [QuickActionController::class, 'store'])->name('websites.quick-actions.store');
        Route::put('websites/{website}/quick-actions/{quickAction}', [QuickActionController::class, 'update'])->name('websites.quick-actions.update');
        Route::patch('websites/{website}/quick-actions/{quickAction}/toggle', [QuickActionController::class, 'toggle'])->name('websites.quick-actions.toggle');
        Route::post('websites/{website}/quick-actions/{quickAction}/duplicate', [QuickActionController::class, 'duplicate'])->name('websites.quick-actions.duplicate');
        Route::post('websites/{website}/quick-actions/reorder', [QuickActionController::class, 'reorder'])->name('websites.quick-actions.reorder');
        Route::delete('websites/{website}/quick-actions/{quickAction}', [QuickActionController::class, 'destroy'])->name('websites.quick-actions.destroy');
    });

    Route::middleware(EnsureRole::class.':super_admin,owner,admin,manager,agent')->group(function () {
        Route::get('websites/{website}/conversations', [ConversationController::class, 'index'])->name('websites.conversations.index');
        Route::get('websites/{website}/conversations/{conversation}', [ConversationController::class, 'show'])->name('websites.conversations.show');
        Route::get('websites/{website}/conversations/{conversation}/messages', [ConversationController::class, 'messages'])->name('websites.conversations.messages');
        Route::post('websites/{website}/conversations/{conversation}/reply', [ConversationController::class, 'reply'])->name('websites.conversations.reply');
        Route::post('websites/{website}/conversations/{conversation}/close', [ConversationController::class, 'close'])->name('websites.conversations.close');
        Route::post('websites/{website}/conversations/{conversation}/reopen', [ConversationController::class, 'reopen'])->name('websites.conversations.reopen');
        Route::post('websites/{website}/conversations/{conversation}/notes', [ConversationController::class, 'storeNote'])->name('websites.conversations.notes.store');
        Route::post('websites/{website}/conversations/{conversation}/return-to-ai', [ConversationController::class, 'returnToAi'])->name('websites.conversations.return-to-ai');
    });

    Route::middleware(EnsureRole::class.':super_admin,owner,admin,manager')->group(function () {
        Route::post('websites/{website}/qa-pairs', [QaPairController::class, 'store'])->name('websites.qa.store');
        Route::post('websites/{website}/qa-pairs/import', [QaPairController::class, 'import'])->name('websites.qa.import');
        Route::delete('websites/{website}/qa-pairs/{qaPair}', [QaPairController::class, 'destroy'])->name('websites.qa.destroy');
        Route::post('websites/{website}/suggested-questions', [SuggestedQuestionController::class, 'store'])->name('websites.questions.store');
        Route::delete('websites/{website}/suggested-questions/{question}', [SuggestedQuestionController::class, 'destroy'])->name('websites.questions.destroy');
    });
});

Route::bind('website', function ($value) {
    $website = Website::findOrFail($value);
    if (auth()->check() && auth()->user()->organization_id && $website->organization_id !== auth()->user()->organization_id) {
        abort(403);
    }

    return $website;
});

Route::bind('conversation', fn ($value) => \App\Models\Conversation::findOrFail($value));
Route::bind('lead', fn ($value) => \App\Models\Lead::findOrFail($value));
Route::bind('organization', fn ($value) => \App\Models\Organization::findOrFail($value));
Route::bind('user', function ($value) {
    $user = \App\Models\User::findOrFail($value);
    if (auth()->check() && auth()->user()->organization_id && $user->organization_id !== auth()->user()->organization_id) {
        abort(403);
    }

    return $user;
});
Route::bind('cannedResponse', fn ($value) => \App\Models\CannedResponse::findOrFail($value));
Route::bind('source', fn ($value) => \App\Models\KnowledgeSource::findOrFail($value));
