<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url')->nullable();
            $table->string('bot_token', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('chatbot_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->unique()->constrained()->cascadeOnDelete();

            // Identity & appearance
            $table->string('bot_name')->default('Assistant');
            $table->string('avatar_url')->nullable();
            $table->string('primary_color', 20)->default('#4F46E5');
            $table->string('secondary_color', 20)->default('#6366F1');
            $table->string('theme_mode', 20)->default('light');
            $table->string('position', 10)->default('right');
            $table->string('locale', 10)->default('en');

            // Messages
            $table->text('welcome_message')->nullable();
            $table->string('typing_indicator_text')->default('Typing...');
            $table->text('offline_message')->nullable();
            $table->text('outside_hours_message')->nullable();

            // AI
            $table->string('ai_provider')->default('openai');
            $table->string('ai_model')->default('gpt-4o-mini');
            $table->decimal('ai_temperature', 3, 2)->default(0.70);
            $table->decimal('confidence_threshold', 3, 2)->default(0.75);
            $table->text('system_prompt')->nullable();
            $table->boolean('ai_enabled')->default(true);

            // Custom code & modules
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();
            $table->json('enabled_modules')->nullable();

            // Security & rate limits
            $table->unsignedInteger('rate_limit_per_minute')->default(30);
            $table->unsignedInteger('rate_limit_per_hour')->default(500);
            $table->boolean('require_domain_validation')->default(true);
            $table->json('security_settings')->nullable();

            $table->timestamps();
        });

        Schema::create('suggested_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('operating_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();
            $table->unique(['website_id', 'day_of_week']);
        });

        Schema::create('allowed_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->timestamps();
            $table->unique(['website_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowed_domains');
        Schema::dropIfExists('operating_hours');
        Schema::dropIfExists('suggested_questions');
        Schema::dropIfExists('chatbot_configurations');
        Schema::dropIfExists('websites');
    }
};
