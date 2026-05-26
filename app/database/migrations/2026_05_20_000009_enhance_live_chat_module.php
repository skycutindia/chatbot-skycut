<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_starred')->default(false)->after('tags');
            $table->boolean('is_pinned')->default(false)->after('is_starred');
            $table->timestamp('snoozed_until')->nullable()->after('is_pinned');
            $table->unsignedSmallInteger('agent_unread_count')->default(0)->after('snoozed_until');
            $table->string('visitor_job_title')->nullable()->after('visitor_company');
            $table->unsignedInteger('visit_count')->default(1)->after('visitor_job_title');
            $table->unsignedSmallInteger('low_confidence_streak')->default(0)->after('visit_count');
            $table->timestamp('first_response_at')->nullable()->after('last_message_at');
            $table->timestamp('resolved_at')->nullable()->after('closed_at');
            $table->foreignId('department_id')->nullable()->after('assigned_user_id');
            $table->text('agent_draft')->nullable()->after('metadata');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });

        Schema::create('agent_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('online');
            $table->unsignedTinyInteger('max_concurrent_chats')->default(5);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 64);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('file_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();
        });

        Schema::create('chat_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 16)->default('#64748b');
            $table->timestamps();
            $table->unique(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_tags');
        Schema::dropIfExists('file_attachments');
        Schema::dropIfExists('chat_events');
        Schema::dropIfExists('agent_statuses');

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        Schema::dropIfExists('departments');

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn([
                'is_starred', 'is_pinned', 'snoozed_until', 'agent_unread_count',
                'visitor_job_title', 'visit_count', 'low_confidence_streak',
                'first_response_at', 'resolved_at', 'department_id', 'agent_draft',
            ]);
        });
    }
};
