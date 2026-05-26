<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('trigger_type', 32);
            $table->json('trigger_config')->nullable();
            $table->string('action_type', 32);
            $table->json('action_config')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'trigger_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_automation_rules');
    }
};
