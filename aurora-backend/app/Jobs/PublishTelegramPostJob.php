<?php

namespace App\Jobs;

use App\DTO\PublishResult;
use App\Models\PostDraft;
use App\Services\Publishing\PublishService;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PublishTelegramPostJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $draftId)
    {
    }

    public function handle(PublishService $publisher, TelegramBotService $bot): void
    {
        $draft = PostDraft::query()->with('user.telegramAccount')->findOrFail($this->draftId);

        try {
            $result = $publisher->publishTelegram($draft);
            $this->notifyUser($bot, $draft->refresh(), $result);
        } catch (Throwable $exception) {
            try {
                $bot->sendMessage(
                    $draft->user->telegramAccount?->telegram_user_id,
                    $exception->getMessage() ?: 'Publishing failed. Your draft is still saved and can be retried.',
                );
            } catch (Throwable) {
                //
            }
        }
    }

    private function notifyUser(TelegramBotService $bot, PostDraft $draft, PublishResult $result): void
    {
        $chatId = $draft->user->telegramAccount?->telegram_user_id;

        if (! $chatId) {
            return;
        }

        $message = $result->success
            ? 'Published successfully to your connected Telegram channel.'
            : 'Publishing failed. Your draft is still saved and can be retried.';

        try {
            $bot->sendMessage($chatId, $message);
        } catch (Throwable) {
            //
        }
    }
}
