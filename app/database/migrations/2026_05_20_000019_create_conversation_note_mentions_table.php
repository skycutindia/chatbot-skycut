<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_note_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentioned_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_note_id', 'mentioned_user_id']);
            $table->index(['mentioned_user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_note_mentions');
    }
};
