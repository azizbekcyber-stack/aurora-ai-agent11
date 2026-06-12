<?php

namespace App\Services\Publishing;

use App\DTO\PublishResult;
use App\Enums\ChannelStatus;
use App\Enums\PublishLogStatus;
use App\Enums\PublishPlatform;
use App\Enums\PublishStrategy;
use App\Exceptions\InvalidDraftStateException;
use App\Models\PostDraft;
use App\Services\Drafts\DraftStateService;
use Throwable;

class PublishService
{
    public function __construct(
        private readonly DraftStateService $state,
        private readonly PublisherResolver $publishers,
    ) {
    }

    public function publishTelegram(PostDraft $draft): PublishResult
    {
        $draft->loadMissing('telegramChannel', 'selectedVariant');
        $this->state->assertCanPublish($draft);

        if (! $draft->telegramChannel) {
            throw new InvalidDraftStateException('No Telegram channel is connected yet. Please connect a channel first.');
        }

        if (! $draft->telegramChannel->bot_can_post_messages) {
            throw new InvalidDraftStateException('The bot does not have permission to post in this channel. Please add the bot as an admin and enable post permission.');
        }

        if ($draft->telegramChannel->status !== ChannelStatus::Connected) {
            throw new InvalidDraftStateException('No Telegram channel is connected yet. Please connect a channel first.');
        }

        $this->state->markPublishing($draft);
        $draft->refresh();

        try {
            $result = $this->publishers->resolve(PublishPlatform::Telegram)->publish($draft);
        } catch (Throwable $exception) {
            $result = PublishResult::failed(PublishStrategy::TextOnly, $exception->getMessage());
        }

        $draft->publishLogs()->create([
            'platform' => PublishPlatform::Telegram,
            'status' => $result->success ? PublishLogStatus::Success : PublishLogStatus::Failed,
            'telegram_message_ids' => $result->messageIds,
            'publish_strategy' => $result->strategy,
            'error_message' => $result->success ? null : $result->errorMessage,
            'published_at' => $result->success ? now() : null,
        ]);

        if ($result->success) {
            $this->state->markPublished($draft);
        } else {
            $this->state->markFailed($draft);
        }

        return $result;
    }
}
