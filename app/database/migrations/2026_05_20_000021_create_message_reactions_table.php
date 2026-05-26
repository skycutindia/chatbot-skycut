<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('reactor_type', 16);
            $table->string('reactor_key', 64);
            $table->string('emoji', 16);
            $table->timestamps();

            $table->unique(['message_id', 'reactor_type', 'reactor_key']);
            $table->index(['conversation_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
