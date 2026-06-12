<?php

namespace Tests\Feature;

use App\Contracts\PostGenerator;
use App\Enums\ChannelStatus;
use App\Enums\DraftSource;
use App\Enums\DraftStatus;
use App\Enums\PublishLogStatus;
use App\Exceptions\InvalidDraftStateException;
use App\Jobs\GeneratePostVariantsJob;
use App\Jobs\PublishTelegramPostJob;
use App\Models\BrandProfile;
use App\Models\PostDraft;
use App\Models\PostVariant;
use App\Models\TelegramChannel;
use App\Models\User;
use App\Services\Drafts\DraftStateService;
use App\Services\Publishing\PublishService;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DraftWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_can_be_created_and_enters_generating_state(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->postJson('/api/v1/drafts', ['prompt' => 'Launch our weekend menu'], [
            'X-Aurora-User-Id' => (string) $user->id,
        ])->assertAccepted()
            ->assertJsonPath('status', DraftStatus::Generating->value);

        $this->assertDatabaseHas('post_drafts', [
            'user_id' => $user->id,
            'prompt' => 'Launch our weekend menu',
            'status' => DraftStatus::Generating->value,
        ]);

        Queue::assertPushed(GeneratePostVariantsJob::class);
    }

    public function test_generation_job_stores_three_variants_and_brand_profile_payload(): void
    {
        config(['services.gemini.key' => null]);
        $user = User::factory()->create();
        BrandProfile::query()->create([
            'user_id' => $user->id,
            'default_language' => 'en',
            'tone' => 'confident but friendly',
            'audience' => 'Telegram subscribers',
            'emoji_level' => 'low',
            'hashtag_style' => 'minimal',
            'banned_words' => ['guaranteed'],
        ]);

        $draft = PostDraft::query()->create([
            'user_id' => $user->id,
            'prompt' => 'Announce a new AI workshop',
            'source' => DraftSource::Web,
            'status' => DraftStatus::Generating,
        ]);

        (new GeneratePostVariantsJob($draft->id))->handle(
            app(PostGenerator::class),
            app(DraftStateService::class),
            app(TelegramBotService::class),
        );

        $draft->refresh();

        $this->assertSame(DraftStatus::Generated, $draft->status);
        $this->assertCount(3, $draft->variants);
        $this->assertDatabaseHas('ai_generation_logs', [
            'post_draft_id' => $draft->id,
            'provider' => 'fake',
            'status' => 'success',
        ]);
        $this->assertSame(
            'confident but friendly',
            $draft->aiGenerationLogs()->first()->request_payload['brand_profile']['tone'],
        );
    }

    public function test_draft_cannot_publish_without_selected_variant(): void
    {
        $draft = $this->draftWithChannel(DraftStatus::Approved);

        $this->expectException(InvalidDraftStateException::class);
        app(PublishService::class)->publishTelegram($draft);
    }

    public function test_draft_cannot_publish_without_approval(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $draft = PostDraft::query()->create([
            'user_id' => $user->id,
            'prompt' => 'A draft',
            'source' => DraftSource::Web,
            'status' => DraftStatus::Generated,
        ]);
        PostVariant::query()->create([
            'post_draft_id' => $draft->id,
            'body' => 'Body',
            'telegram_text' => 'Body',
        ]);

        $this->postJson("/api/v1/drafts/{$draft->id}/publish", [], [
            'X-Aurora-User-Id' => (string) $user->id,
        ])->assertStatus(422)
            ->assertJson(['message' => 'This draft cannot be published because it has not been approved yet.']);

        Queue::assertNotPushed(PublishTelegramPostJob::class);
    }

    public function test_approved_draft_can_move_to_publishing(): void
    {
        $draft = $this->approvedDraftWithVariant();

        app(DraftStateService::class)->markPublishing($draft);

        $this->assertSame(DraftStatus::Publishing, $draft->refresh()->status);
    }

    public function test_publish_failure_stores_error_log(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);

        Http::fake([
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response([
                'ok' => false,
                'description' => 'Telegram rejected the message',
            ], 200),
        ]);

        $draft = $this->approvedDraftWithVariant();
        $result = app(PublishService::class)->publishTelegram($draft);

        $this->assertFalse($result->success);
        $this->assertSame(DraftStatus::Failed, $draft->refresh()->status);
        $this->assertDatabaseHas('publish_logs', [
            'post_draft_id' => $draft->id,
            'status' => PublishLogStatus::Failed->value,
            'error_message' => 'Telegram rejected the message',
        ]);
    }

    public function test_telegram_channel_permission_failure_blocks_publishing(): void
    {
        $draft = $this->approvedDraftWithVariant(botCanPost: false);

        $this->expectException(InvalidDraftStateException::class);
        $this->expectExceptionMessage('The bot does not have permission to post in this channel.');

        app(PublishService::class)->publishTelegram($draft);
    }

    private function draftWithChannel(DraftStatus $status, bool $botCanPost = true): PostDraft
    {
        $user = User::factory()->create();
        $channel = TelegramChannel::query()->create([
            'user_id' => $user->id,
            'chat_id' => '-100123',
            'title' => 'Aurora Channel',
            'bot_can_post_messages' => $botCanPost,
            'status' => $botCanPost ? ChannelStatus::Connected : ChannelStatus::Failed,
            'connected_at' => $botCanPost ? now() : null,
        ]);

        return PostDraft::query()->create([
            'user_id' => $user->id,
            'telegram_channel_id' => $channel->id,
            'prompt' => 'Draft prompt',
            'source' => DraftSource::Web,
            'status' => $status,
        ]);
    }

    private function approvedDraftWithVariant(bool $botCanPost = true): PostDraft
    {
        $draft = $this->draftWithChannel(DraftStatus::Approved, $botCanPost);
        $variant = PostVariant::query()->create([
            'post_draft_id' => $draft->id,
            'title' => 'Variant',
            'body' => 'Body',
            'hashtags' => [],
            'cta' => null,
            'telegram_text' => 'Final Telegram text',
            'risk_flags' => [],
        ]);

        $draft->forceFill(['selected_variant_id' => $variant->id])->save();

        return $draft->refresh();
    }
}
