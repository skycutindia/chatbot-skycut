<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone_number_id');
            $table->string('display_phone')->nullable();
            $table->text('access_token');
            $table->string('verify_token', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'phone_number_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->string('channel', 32)->default('web')->after('mode');
            $table->string('channel_contact_id', 128)->nullable()->after('channel');
            $table->index(['website_id', 'channel', 'channel_contact_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['website_id', 'channel', 'channel_contact_id']);
            $table->dropColumn(['channel', 'channel_contact_id']);
        });

        Schema::dropIfExists('whatsapp_channels');
    }
};
