<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_id', 64)->index();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('status')->default('open');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('page_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['website_id', 'status', 'last_message_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->string('source')->default('user');
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('visitor_id', 64)->nullable();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['website_id', 'event_type', 'created_at']);
        });

        Schema::create('widget_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('bot_token', 64)->index();
            $table->string('identifier', 128);
            $table->string('window', 20);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->unique(['bot_token', 'identifier', 'window']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_rate_limits');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
