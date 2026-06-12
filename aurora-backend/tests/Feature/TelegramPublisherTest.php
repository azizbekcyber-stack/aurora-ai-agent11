<?php

namespace Tests\Feature;

use App\Enums\ChannelStatus;
use App\Enums\DraftSource;
use App\Enums\DraftStatus;
use App\Enums\PublishStrategy;
use App\Models\PostDraft;
use App\Models\PostVariant;
use App\Models\TelegramChannel;
use App\Models\User;
use App\Services\Telegram\TelegramPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TelegramPublisherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.bot_token' => 'test-token']);
    }

    public function test_send_message_is_called_for_text_only_post(): void
    {
        Http::fake([
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 101],
            ]),
        ]);

        $result = app(TelegramPublisher::class)->publish($this->approvedDraft('Short text'));

        $this->assertTrue($result->success);
        $this->assertSame(PublishStrategy::TextOnly, $result->strategy);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Short text');
    }

    public function test_send_photo_is_called_when_image_has_short_caption(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('telegram/test.jpg', 'fake-image');

        Http::fake([
            'https://api.telegram.org/bottest-token/sendPhoto' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 202],
            ]),
        ]);

        $result = app(TelegramPublisher::class)->publish($this->approvedDraft('Short caption', 'telegram/test.jpg'));

        $this->assertTrue($result->success);
        $this->assertSame(PublishStrategy::PhotoWithCaption, $result->strategy);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/sendPhoto'));
    }

    public function test_long_image_post_uses_photo_then_text_fallback(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('telegram/test.jpg', 'fake-image');

        Http::fake([
            'https://api.telegram.org/bottest-token/sendPhoto' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 301],
            ]),
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 302],
            ]),
        ]);

        $result = app(TelegramPublisher::class)->publish($this->approvedDraft(str_repeat('A', 1100), 'telegram/test.jpg'));

        $this->assertTrue($result->success);
        $this->assertSame(PublishStrategy::PhotoThenText, $result->strategy);
        Http::assertSentCount(2);
    }

    public function test_telegram_errors_are_captured(): void
    {
        Http::fake([
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response([
                'ok' => false,
                'description' => 'Bad Request',
            ]),
        ]);

        $result = app(TelegramPublisher::class)->publish($this->approvedDraft('Short text'));

        $this->assertFalse($result->success);
        $this->assertSame('Bad Request', $result->errorMessage);
    }

    private function approvedDraft(string $telegramText, ?string $imagePath = null): PostDraft
    {
        $user = User::factory()->create();
        $channel = TelegramChannel::query()->create([
            'user_id' => $user->id,
            'chat_id' => '-100123',
            'title' => 'Aurora Channel',
            'bot_can_post_messages' => true,
            'status' => ChannelStatus::Connected,
            'connected_at' => now(),
        ]);
        $draft = PostDraft::query()->create([
            'user_id' => $user->id,
            'telegram_channel_id' => $channel->id,
            'prompt' => 'Prompt',
            'image_path' => $imagePath,
            'source' => DraftSource::Web,
            'status' => DraftStatus::Approved,
        ]);
        $variant = PostVariant::query()->create([
            'post_draft_id' => $draft->id,
            'title' => 'Variant',
            'body' => $telegramText,
            'hashtags' => [],
            'cta' => null,
            'telegram_text' => $telegramText,
            'risk_flags' => [],
        ]);

        $draft->forceFill(['selected_variant_id' => $variant->id])->save();

        return $draft->refresh();
    }
}
