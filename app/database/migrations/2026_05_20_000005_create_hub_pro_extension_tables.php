<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('slug');
            $table->string('timezone', 64)->default('UTC')->after('logo_url');
        });

        Schema::table('websites', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('url');
            $table->string('logo_url')->nullable()->after('domain');
            $table->json('brand_colors')->nullable()->after('logo_url');
            $table->string('language', 10)->default('en')->after('brand_colors');
            $table->string('timezone', 64)->default('UTC')->after('language');
            $table->string('verification_token', 64)->nullable()->unique()->after('bot_token');
            $table->boolean('widget_enabled')->default(true)->after('is_active');
        });

        Schema::table('chatbot_configurations', function (Blueprint $table) {
            $table->unsignedInteger('max_tokens')->default(1024)->after('ai_temperature');
            $table->unsignedSmallInteger('trigger_delay_seconds')->default(3)->after('position');
            $table->boolean('auto_open')->default(false)->after('trigger_delay_seconds');
            $table->json('auto_open_rules')->nullable()->after('auto_open');
            $table->boolean('sound_enabled')->default(true)->after('auto_open_rules');
            $table->boolean('typing_animation')->default(true)->after('sound_enabled');
            $table->json('handoff_triggers')->nullable()->after('confidence_threshold');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->string('mode')->default('ai')->after('status');
            $table->string('priority')->default('normal')->after('mode');
            $table->json('tags')->nullable()->after('priority');
            $table->timestamp('sla_due_at')->nullable()->after('tags');
            $table->string('source_url', 2048)->nullable()->after('page_url');
            $table->string('visitor_phone', 40)->nullable()->after('visitor_email');
            $table->string('visitor_company')->nullable()->after('visitor_phone');
            $table->string('ip_address', 45)->nullable()->after('metadata');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->json('utm_params')->nullable()->after('user_agent');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('remember_token');
            $table->text('two_factor_secret')->nullable()->after('two_factor_confirmed_at');
            $table->json('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('locked_until')->nullable()->after('is_active');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('locked_until');
            $table->json('allowed_ips')->nullable()->after('failed_login_attempts');
        });

        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('status', 20)->default('success');
            $table->timestamp('logged_in_at')->useCurrent();
            $table->index(['user_id', 'logged_in_at']);
        });

        Schema::create('quick_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('action_type')->default('url');
            $table->string('action_value', 2048)->nullable();
            $table->json('display_rules')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('company')->nullable();
            $table->string('website_url')->nullable();
            $table->string('status')->default('new');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_url', 2048)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('device_info')->nullable();
            $table->json('utm_params')->nullable();
            $table->longText('chat_transcript')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status', 'created_at']);
            $table->index(['website_id', 'email']);
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('conversation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::table('qa_pairs', function (Blueprint $table) {
            $table->json('question_variations')->nullable()->after('question');
            $table->string('category')->nullable()->after('answer');
            $table->json('tags')->nullable()->after('category');
            $table->boolean('is_published')->default(true)->after('is_active');
            $table->unsignedInteger('version')->default(1)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('qa_pairs', function (Blueprint $table) {
            $table->dropColumn(['question_variations', 'category', 'tags', 'is_published', 'version']);
        });
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('conversation_notes');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('quick_actions');
        Schema::dropIfExists('login_histories');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_confirmed_at', 'two_factor_secret', 'two_factor_recovery_codes',
                'locked_until', 'failed_login_attempts', 'allowed_ips',
            ]);
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn([
                'mode', 'priority', 'tags', 'sla_due_at', 'source_url',
                'visitor_phone', 'visitor_company', 'ip_address', 'user_agent', 'utm_params',
            ]);
        });
        Schema::table('chatbot_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'max_tokens', 'trigger_delay_seconds', 'auto_open', 'auto_open_rules',
                'sound_enabled', 'typing_animation', 'handoff_triggers',
            ]);
        });
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn([
                'domain', 'logo_url', 'brand_colors', 'language', 'timezone',
                'verification_token', 'widget_enabled',
            ]);
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['logo_url', 'timezone']);
        });
    }
};
