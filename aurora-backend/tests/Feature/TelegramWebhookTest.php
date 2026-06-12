<?php

namespace Tests\Feature;

use App\Models\TelegramAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_webhook_accepts_valid_start_update(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        config(['services.telegram.webhook_secret' => null]);

        Http::fake([
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 10],
            ]),
        ]);

        $this->postJson('/webhook/telegram', [
            'update_id' => 1,
            'message' => [
                'message_id' => 100,
                'chat' => ['id' => 555, 'type' => 'private'],
                'from' => [
                    'id' => 555,
                    'is_bot' => false,
                    'first_name' => 'Ari',
                    'username' => 'ari_admin',
                ],
                'text' => '/start',
            ],
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('telegram_accounts', [
            'telegram_user_id' => '555',
            'username' => 'ari_admin',
        ]);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/sendMessage'));
        $this->assertSame('555', TelegramAccount::first()->telegram_user_id);
    }
}
