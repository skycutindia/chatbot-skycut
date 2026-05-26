<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('organizations', 'subscription_plan_id')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subscription_plan_id');
            });
        }

        if (Schema::hasColumn('organizations', 'trial_ends_at')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropColumn('trial_ends_at');
            });
        }

        Schema::dropIfExists('subscription_plans');
    }

    public function down(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('max_websites')->default(5);
            $table->unsignedInteger('max_agents')->default(10);
            $table->unsignedBigInteger('monthly_messages')->default(10000);
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable();
        });
    }
};
