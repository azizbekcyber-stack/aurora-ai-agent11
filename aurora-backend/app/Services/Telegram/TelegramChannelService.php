<?php

namespace App\Services\Telegram;

use App\Enums\ChannelStatus;
use App\Models\TelegramChannel;
use App\Models\User;
use Throwable;

class TelegramChannelService
{
    public function __construct(private readonly TelegramBotService $bot)
    {
    }

    public function connectFromIdentifier(User $user, string $identifier): TelegramChannel
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            throw new \InvalidArgumentException('Channel username or ID is required.');
        }

        if (! str_starts_with($identifier, '@') && ! str_starts_with($identifier, '-')) {
            $identifier = '@'.$identifier;
        }

        return $this->connectFromChat($user, $this->bot->getChat($identifier));
    }

    public function connectFromForward(User $user, array $message): ?TelegramChannel
    {
        $chat = $message['forward_origin']['chat']
            ?? $message['forward_from_chat']
            ?? null;

        if (! is_array($chat)) {
            return null;
        }

        return $this->connectFromChat($user, $chat);
    }

    public function connectFromChat(User $user, array $chat): TelegramChannel
    {
        $chatId = (string) ($chat['id'] ?? '');

        if ($chatId === '') {
            throw new \InvalidArgumentException('Forwarded message did not include a channel ID.');
        }

        $canPost = false;

        try {
            $canPost = $this->botCanPost($chatId);
        } catch (Throwable) {
            $canPost = false;
        }

        return TelegramChannel::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'chat_id' => $chatId,
                'username' => $chat['username'] ?? null,
                'title' => $chat['title'] ?? null,
                'bot_can_post_messages' => $canPost,
                'status' => $canPost ? ChannelStatus::Connected : ChannelStatus::Failed,
                'connected_at' => $canPost ? now() : null,
                'last_checked_at' => now(),
            ],
        );
    }

    public function botCanPost(string $chatId): bool
    {
        $bot = $this->bot->getMe();
        $member = $this->bot->getChatMember($chatId, $bot['id']);

        if (($member['status'] ?? null) === 'creator') {
            return true;
        }

        return ($member['status'] ?? null) === 'administrator'
            && (bool) ($member['can_post_messages'] ?? false);
    }
}
