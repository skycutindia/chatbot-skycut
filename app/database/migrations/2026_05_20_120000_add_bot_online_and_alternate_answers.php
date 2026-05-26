<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('chatbot_configurations', 'bot_online')) {
                $table->boolean('bot_online')->default(true)->after('typing_animation');
            }
        });

        Schema::table('qa_pairs', function (Blueprint $table) {
            if (! Schema::hasColumn('qa_pairs', 'alternate_answers')) {
                $table->json('alternate_answers')->nullable()->after('answer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_configurations', function (Blueprint $table) {
            if (Schema::hasColumn('chatbot_configurations', 'bot_online')) {
                $table->dropColumn('bot_online');
            }
        });

        Schema::table('qa_pairs', function (Blueprint $table) {
            if (Schema::hasColumn('qa_pairs', 'alternate_answers')) {
                $table->dropColumn('alternate_answers');
            }
        });
    }
};
