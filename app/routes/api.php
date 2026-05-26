<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\WidgetAttachmentController;
use App\Http\Controllers\Api\WidgetChatController;
use App\Http\Controllers\Api\WidgetConfigController;
use App\Http\Controllers\Api\WidgetEventController;
use App\Http\Middleware\ResolveWebsiteByBotToken;
use App\Http\Middleware\ValidateWidgetDomain;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('api.health');

Route::match(['get', 'post'], '/webhooks/whatsapp/{organizationSlug}', [WhatsAppWebhookController::class, 'handle'])
    ->name('api.whatsapp.webhook');

Route::prefix('widget/{botToken}')
    ->middleware([ResolveWebsiteByBotToken::class, ValidateWidgetDomain::class])
    ->group(function () {
        Route::get('/config', [WidgetConfigController::class, 'show']);
        Route::post('/start', [WidgetChatController::class, 'start']);
        Route::post('/close', [WidgetChatController::class, 'close']);
        Route::post('/chat', [WidgetChatController::class, 'store']);
        Route::post('/handoff', [WidgetChatController::class, 'requestHandoff']);
        Route::post('/read', [WidgetChatController::class, 'markRead']);
        Route::post('/reactions', [WidgetChatController::class, 'react']);
        Route::post('/attachments', [WidgetAttachmentController::class, 'store'])->name('api.widget.attachments.store');
        Route::get('/attachments/{attachment}', [WidgetAttachmentController::class, 'download'])->name('api.widget.attachments.download');
        Route::get('/poll', [WidgetChatController::class, 'poll']);
        Route::get('/history', [WidgetChatController::class, 'history']);
        Route::post('/events', [WidgetEventController::class, 'store']);
    });
