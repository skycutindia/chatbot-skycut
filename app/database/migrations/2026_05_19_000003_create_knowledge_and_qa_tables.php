<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['website_id', 'slug']);
        });

        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content');
            $table->string('source_url')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
            $table->unique(['website_id', 'slug']);

            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['title', 'content']);
            }
        });

        Schema::create('knowledge_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['website_id', 'name']);
        });

        Schema::create('knowledge_article_tag', function (Blueprint $table) {
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['knowledge_article_id', 'knowledge_tag_id']);
        });

        Schema::create('knowledge_synonyms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('term');
            $table->json('synonyms');
            $table->timestamps();
            $table->unique(['website_id', 'term']);
        });

        Schema::create('qa_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->json('trigger_keywords')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('trigger_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('keyword');
            $table->string('action')->default('respond');
            $table->text('response')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_type');
            $table->json('trigger_config')->nullable();
            $table->string('action')->default('assign_agent');
            $table->json('action_config')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('key');
            $table->text('value');
            $table->timestamps();
            $table->unique(['website_id', 'locale', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
        Schema::dropIfExists('escalation_rules');
        Schema::dropIfExists('trigger_keywords');
        Schema::dropIfExists('qa_pairs');
        Schema::dropIfExists('knowledge_synonyms');
        Schema::dropIfExists('knowledge_article_tag');
        Schema::dropIfExists('knowledge_tags');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_categories');
    }
};
