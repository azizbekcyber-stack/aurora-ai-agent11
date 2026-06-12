<?php

namespace App\Services\Telegram;

use App\Contracts\SocialPublisher;
use App\DTO\PublishResult;
use App\Enums\ChannelStatus;
use App\Enums\PublishStrategy;
use App\Models\PostDraft;
use Throwable;

class TelegramPublisher implements SocialPublisher
{
    private const PHOTO_CAPTION_LIMIT = 1024;

    public function __construct(private readonly TelegramBotService $bot)
    {
    }

    public function publish(PostDraft $draft): PublishResult
    {
        $draft->loadMissing('telegramChannel', 'selectedVariant');
        $channel = $draft->telegramChannel;
        $variant = $draft->selectedVariant;

        if (! $channel) {
            return PublishResult::failed(PublishStrategy::TextOnly, 'No Telegram channel is connected yet. Please connect a channel first.');
        }

        if (! $channel->bot_can_post_messages) {
            return PublishResult::failed(PublishStrategy::TextOnly, 'The bot does not have permission to post in this channel. Please add the bot as an admin and enable post permission.');
        }

        if ($channel->status !== ChannelStatus::Connected) {
            return PublishResult::failed(PublishStrategy::TextOnly, 'No Telegram channel is connected yet. Please connect a channel first.');
        }

        if (! $variant) {
            return PublishResult::failed(PublishStrategy::TextOnly, 'Draft cannot be published without a selected variant.');
        }

        $messageIds = [];
        $text = $variant->telegram_text;

        try {
            if (! $draft->image_path) {
                $response = $this->bot->sendMessage($channel->chat_id, $text);
                $messageIds[] = $response['result']['message_id'] ?? null;

                return PublishResult::success(PublishStrategy::TextOnly, array_values(array_filter($messageIds)));
            }

            if (mb_strlen($text) <= self::PHOTO_CAPTION_LIMIT) {
                $response = $this->bot->sendPhoto($channel->chat_id, $draft->image_path, $text);
                $messageIds[] = $response['result']['message_id'] ?? null;

                return PublishResult::success(PublishStrategy::PhotoWithCaption, array_values(array_filter($messageIds)));
            }

            $photoResponse = $this->bot->sendPhoto($channel->chat_id, $draft->image_path);
            $textResponse = $this->bot->sendMessage($channel->chat_id, $text);
            $messageIds[] = $photoResponse['result']['message_id'] ?? null;
            $messageIds[] = $textResponse['result']['message_id'] ?? null;

            return PublishResult::success(PublishStrategy::PhotoThenText, array_values(array_filter($messageIds)));
        } catch (Throwable $exception) {
            return PublishResult::failed(
                $draft->image_path ? PublishStrategy::PhotoThenText : PublishStrategy::TextOnly,
                $exception->getMessage(),
            );
        }
    }
}
