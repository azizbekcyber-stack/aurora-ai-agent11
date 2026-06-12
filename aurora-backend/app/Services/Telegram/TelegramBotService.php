<?php

namespace App\Services\Telegram;

use App\Exceptions\TelegramApiException;
use App\Models\PostDraft;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TelegramBotService
{
    public function sendMessage(string|int $chatId, string $text, ?array $replyMarkup = null): array
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        return $this->request('sendMessage', $payload);
    }

    public function sendPhoto(string|int $chatId, string $photo, ?string $caption = null): array
    {
        $payload = [
            'chat_id' => $chatId,
            'parse_mode' => 'HTML',
        ];

        if ($caption !== null && $caption !== '') {
            $payload['caption'] = $caption;
        }

        if (Storage::disk('local')->exists($photo)) {
            $absolutePath = Storage::disk('local')->path($photo);

            return $this->requestWithAttachment('sendPhoto', $payload, 'photo', $absolutePath);
        }

        $payload['photo'] = $photo;

        return $this->request('sendPhoto', $payload);
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): array
    {
        $payload = ['callback_query_id' => $callbackQueryId];

        if ($text) {
            $payload['text'] = $text;
        }

        return $this->request('answerCallbackQuery', $payload);
    }

    public function getMe(): array
    {
        return $this->request('getMe')['result'];
    }

    public function getChat(string $chatIdOrUsername): array
    {
        return $this->request('getChat', ['chat_id' => $chatIdOrUsername])['result'];
    }

    public function getChatMember(string $chatIdOrUsername, string|int $userId): array
    {
        return $this->request('getChatMember', [
            'chat_id' => $chatIdOrUsername,
            'user_id' => $userId,
        ])['result'];
    }

    public function getFile(string $fileId): array
    {
        return $this->request('getFile', ['file_id' => $fileId])['result'];
    }

    public function downloadFile(string $filePath): string
    {
        $token = $this->token();
        $response = Http::timeout(30)->get("https://api.telegram.org/file/bot{$token}/{$filePath}");

        if ($response->failed()) {
            throw new TelegramApiException('Telegram file download failed.');
        }

        return $response->body();
    }

    public function sendGeneratedVariants(PostDraft $draft): void
    {
        $draft->loadMissing('user.telegramAccount', 'variants');
        $chatId = $draft->user->telegramAccount?->telegram_user_id;

        if (! $chatId) {
            return;
        }

        $lines = [
            "✨ <b>Your 3 post variants are ready</b>",
            "<i>Pick the best direction, regenerate if you want a fresh angle, or cancel this draft.</i>",
        ];

        foreach ($draft->variants as $index => $variant) {
            $number = $index + 1;
            $lines[] = "\n<b>Option {$number} · ".e($variant->title ?: 'Untitled')."</b>\n".e($variant->telegram_text);
        }

        $buttons = $draft->variants->values()->map(function ($variant, int $index) use ($draft): array {
            return [
                'text' => '✅ Choose Option '.($index + 1),
                'callback_data' => "variant:{$draft->id}:{$variant->id}",
            ];
        })->chunk(1)->map(fn ($row) => $row->values()->all())->values()->all();

        $buttons[] = [
            ['text' => '🔄 Regenerate', 'callback_data' => "regenerate:{$draft->id}"],
            ['text' => '✖️ Cancel', 'callback_data' => "cancel:{$draft->id}"],
        ];

        $this->sendMessage($chatId, implode("\n", $lines), ['inline_keyboard' => $buttons]);
    }

    public function sendApprovalRequest(PostDraft $draft): void
    {
        $draft->loadMissing('user.telegramAccount', 'selectedVariant');
        $chatId = $draft->user->telegramAccount?->telegram_user_id;

        if (! $chatId || ! $draft->selectedVariant) {
            return;
        }

        $text = "✅ <b>Selected draft</b>\n"
            .e($draft->selectedVariant->telegram_text)
            ."\n\n🚀 <b>Ready to publish?</b>\n"
            ."I’ll send this to your connected Telegram channel.";

        $this->sendMessage($chatId, $text, [
            'inline_keyboard' => [
                [
                    ['text' => '🚀 Approve & Publish', 'callback_data' => "approve:{$draft->id}"],
                ],
                [
                    ['text' => '✖️ Cancel Draft', 'callback_data' => "cancel:{$draft->id}"],
                ],
            ],
        ]);
    }

    private function request(string $method, array $payload = []): array
    {
        $token = $this->token();
        $response = Http::acceptJson()
            ->asJson()
            ->timeout(30)
            ->post("https://api.telegram.org/bot{$token}/{$method}", $payload);

        $json = $response->json();

        if ($response->failed() || ! ($json['ok'] ?? false)) {
            throw new TelegramApiException($json['description'] ?? "Telegram {$method} failed.");
        }

        return $json;
    }

    private function requestWithAttachment(string $method, array $payload, string $field, string $path): array
    {
        $token = $this->token();
        $response = Http::acceptJson()
            ->attach($field, fopen($path, 'r'), basename($path))
            ->timeout(30)
            ->post("https://api.telegram.org/bot{$token}/{$method}", $payload);

        $json = $response->json();

        if ($response->failed() || ! ($json['ok'] ?? false)) {
            throw new TelegramApiException($json['description'] ?? "Telegram {$method} failed.");
        }

        return $json;
    }

    private function token(): string
    {
        $token = (string) config('services.telegram.bot_token');

        if ($token === '') {
            throw new TelegramApiException('Telegram bot token is not configured.');
        }

        return $token;
    }
}
