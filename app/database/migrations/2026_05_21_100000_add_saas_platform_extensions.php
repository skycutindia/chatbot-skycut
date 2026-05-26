<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            if (! Schema::hasColumn('websites', 'category')) {
                $table->string('category')->nullable()->after('url');
            }
            if (! Schema::hasColumn('websites', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('category');
            }
            if (! Schema::hasColumn('websites', 'bot_description')) {
                $table->text('bot_description')->nullable()->after('contact_email');
            }
        });

        Schema::table('chatbot_configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('chatbot_configurations', 'bot_description')) {
                $table->text('bot_description')->nullable()->after('bot_name');
            }
            if (! Schema::hasColumn('chatbot_configurations', 'fallback_message')) {
                $table->text('fallback_message')->nullable()->after('outside_hours_message');
            }
            if (! Schema::hasColumn('chatbot_configurations', 'ai_tone')) {
                $table->string('ai_tone', 64)->nullable()->after('fallback_message');
            }
            if (! Schema::hasColumn('chatbot_configurations', 'widget_channels')) {
                $table->json('widget_channels')->nullable()->after('security_settings');
            }
            if (! Schema::hasColumn('chatbot_configurations', 'config_version')) {
                $table->unsignedBigInteger('config_version')->default(1)->after('widget_channels');
            }
        });

        Schema::create('unanswered_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->text('visitor_message');
            $table->string('detected_intent')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('source', 40)->default('fallback');
            $table->string('status', 20)->default('open');
            $table->foreignId('resolved_qa_pair_id')->nullable()->constrained('qa_pairs')->nullOnDelete();
            $table->text('admin_answer')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['website_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unanswered_questions');

        Schema::table('chatbot_configurations', function (Blueprint $table) {
            foreach (['bot_description', 'fallback_message', 'ai_tone', 'widget_channels', 'config_version'] as $col) {
                if (Schema::hasColumn('chatbot_configurations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('websites', function (Blueprint $table) {
            foreach (['category', 'contact_email', 'bot_description'] as $col) {
                if (Schema::hasColumn('websites', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
