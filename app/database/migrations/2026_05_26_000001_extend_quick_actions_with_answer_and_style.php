<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_actions', function (Blueprint $table) {
            $table->string('color', 32)->nullable()->after('icon');
            $table->text('custom_answer')->nullable()->after('action_value');
            $table->string('description', 255)->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('quick_actions', function (Blueprint $table) {
            $table->dropColumn(['color', 'custom_answer', 'description']);
        });
    }
};
