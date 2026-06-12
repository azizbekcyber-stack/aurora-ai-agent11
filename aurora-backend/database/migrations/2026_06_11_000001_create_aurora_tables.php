<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('telegram_user_id')->unique();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('pending_action')->nullable();
            $table->timestamps();
        });

        Schema::create('telegram_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('chat_id')->unique();
            $table->string('username')->nullable();
            $table->string('title')->nullable();
            $table->boolean('bot_can_post_messages')->default(false);
            $table->string('status')->default('pending');
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('default_language')->default('en');
            $table->string('tone')->nullable();
            $table->string('audience')->nullable();
            $table->string('emoji_level')->default('medium');
            $table->string('hashtag_style')->default('normal');
            $table->json('banned_words')->nullable();
            $table->timestamps();
        });

        Schema::create('post_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->text('prompt');
            $table->string('image_path')->nullable();
            $table->string('source')->default('telegram');
            $table->string('status')->default('draft')->index();
            $table->unsignedBigInteger('selected_variant_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('post_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_draft_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('body');
            $table->json('hashtags')->nullable();
            $table->text('cta')->nullable();
            $table->text('telegram_text');
            $table->json('risk_flags')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_draft_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('model');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('publish_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_draft_id')->constrained()->cascadeOnDelete();
            $table->string('platform')->default('telegram');
            $table->string('status');
            $table->json('telegram_message_ids')->nullable();
            $table->string('publish_strategy')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publish_logs');
        Schema::dropIfExists('ai_generation_logs');
        Schema::dropIfExists('post_variants');
        Schema::dropIfExists('post_drafts');
        Schema::dropIfExists('brand_profiles');
        Schema::dropIfExists('telegram_channels');
        Schema::dropIfExists('telegram_accounts');
    }
};
