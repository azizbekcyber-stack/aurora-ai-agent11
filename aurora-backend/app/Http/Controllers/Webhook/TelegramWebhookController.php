<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\ChannelStatus;
use App\Enums\DraftSource;
use App\Enums\DraftStatus;
use App\Exceptions\InvalidDraftStateException;
use App\Http\Controllers\Controller;
use App\Jobs\GeneratePostVariantsJob;
use App\Jobs\PublishTelegramPostJob;
use App\Models\PostDraft;
use App\Models\PostVariant;
use App\Models\TelegramAccount;
use App\Models\User;
use App\Services\Drafts\DraftStateService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramChannelService;
use App\Services\Telegram\TelegramFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly DraftStateService $state,
        private readonly TelegramBotService $bot,
        private readonly TelegramFileService $files,
        private readonly TelegramChannelService $channels,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $secret = config('services.telegram.webhook_secret');

        if (filled($secret) && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            abort(403);
        }

        $update = $request->all();

        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);

            return response()->json(['ok' => true]);
        }

        if (isset($update['message'])) {
            $this->handleMessage($update['message']);

            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true, 'ignored' => true]);
    }

    private function handleMessage(array $message): void
    {
        $from = $message['from'] ?? null;

        if (! is_array($from)) {
            return;
        }

        $account = $this->accountFor($from);
        $chatId = (string) ($message['chat']['id'] ?? $account->telegram_user_id);
        $text = trim((string) ($message['text'] ?? $message['caption'] ?? ''));

        if ($this->isCommand($text, '/start')) {
            $account->forceFill(['pending_action' => null])->save();
            $this->bot->sendMessage($chatId, "Welcome to Aurora.\n\nSend /connect_channel to connect your Telegram channel, then send me a post idea or an image with a caption.");

            return;
        }

        if ($this->isCommand($text, '/help')) {
            $this->bot->sendMessage($chatId, "Commands:\n/start - start Aurora\n/connect_channel - connect one channel\n\nAfter your channel is connected, send a text prompt or an image with a caption. Aurora will generate 3 variants and wait for your approval before publishing.");

            return;
        }

        if ($this->isCommand($text, '/connect_channel')) {
            $argument = trim(Str::after($text, ' '));

            if ($argument !== '' && $argument !== $text) {
                $this->connectChannelFromText($account, $chatId, $argument);

                return;
            }

            $account->forceFill(['pending_action' => 'connect_channel'])->save();
            $this->bot->sendMessage($chatId, "Add this bot as an admin to your target channel with post permission. Then send the channel username, for example @your_channel, or forward a message from that channel.");

            return;
        }

        if ($account->pending_action === 'connect_channel') {
            $connected = $this->channels->connectFromForward($account->user, $message);

            if ($connected) {
                $account->forceFill(['pending_action' => null])->save();
                $this->sendChannelConnectionResult($chatId, $connected);

                return;
            }

            if ($text !== '') {
                $this->connectChannelFromText($account, $chatId, $text);

                return;
            }

            $this->bot->sendMessage($chatId, 'Please send the channel username or forward a message from the channel.');

            return;
        }

        $this->createDraftFromMessage($account, $chatId, $message, $text);
    }

    private function handleCallback(array $callback): void
    {
        $from = $callback['from'] ?? null;
        $data = (string) ($callback['data'] ?? '');
        $callbackId = (string) ($callback['id'] ?? '');

        if (! is_array($from) || $data === '') {
            return;
        }

        $account = $this->accountFor($from);
        $chatId = $account->telegram_user_id;

        try {
            if ($callbackId !== '') {
                $this->bot->answerCallbackQuery($callbackId);
            }
        } catch (Throwable) {
            //
        }

        [$action, $draftId, $variantId] = array_pad(explode(':', $data), 3, null);
        $draft = PostDraft::query()->whereBelongsTo($account->user)->findOrFail($draftId);

        try {
            match ($action) {
                'variant' => $this->selectVariant($draft, (int) $variantId),
                'approve' => $this->approveAndPublish($draft),
                'regenerate' => $this->regenerate($draft),
                'cancel' => $this->cancel($draft),
                default => $this->bot->sendMessage($chatId, 'Unknown action.'),
            };
        } catch (InvalidDraftStateException $exception) {
            $this->bot->sendMessage($chatId, $exception->getMessage());
        }
    }

    private function createDraftFromMessage(TelegramAccount $account, string $chatId, array $message, string $text): void
    {
        $channel = $account->user->telegramChannel;

        if (! $channel || $channel->status !== ChannelStatus::Connected) {
            $this->bot->sendMessage($chatId, 'No Telegram channel is connected yet. Please connect a channel first.');

            return;
        }

        if (! $channel->bot_can_post_messages) {
            $this->bot->sendMessage($chatId, 'The bot does not have permission to post in this channel. Please add the bot as an admin and enable post permission.');

            return;
        }

        if ($text === '') {
            $this->bot->sendMessage($chatId, 'Please send a text prompt, or send an image with a caption prompt.');

            return;
        }

        try {
            $imagePath = $this->files->downloadLargestPhoto($message, $account->user_id);
        } catch (Throwable $exception) {
            $this->bot->sendMessage($chatId, 'Could not download the image. Please try a JPG, PNG, or WebP image under 10 MB.');

            return;
        }

        $draft = PostDraft::query()->create([
            'user_id' => $account->user_id,
            'telegram_channel_id' => $channel->id,
            'prompt' => $text,
            'image_path' => $imagePath,
            'source' => DraftSource::Telegram,
            'status' => DraftStatus::Draft,
        ]);

        $this->state->markGenerating($draft);
        GeneratePostVariantsJob::dispatch($draft->id);
        $this->bot->sendMessage($chatId, 'Generating 3 post variants...');
    }

    private function connectChannelFromText(TelegramAccount $account, string $chatId, string $identifier): void
    {
        try {
            $channel = $this->channels->connectFromIdentifier($account->user, $identifier);
            $account->forceFill(['pending_action' => null])->save();
            $this->sendChannelConnectionResult($chatId, $channel);
        } catch (Throwable) {
            $this->bot->sendMessage($chatId, 'The bot does not have permission to post in this channel. Please add the bot as an admin and enable post permission.');
        }
    }

    private function sendChannelConnectionResult(string $chatId, object $channel): void
    {
        if ($channel->status === ChannelStatus::Connected) {
            $this->bot->sendMessage($chatId, 'Channel connected. Send me a post idea when you are ready.');

            return;
        }

        $this->bot->sendMessage($chatId, 'The bot does not have permission to post in this channel. Please add the bot as an admin and enable post permission.');
    }

    private function selectVariant(PostDraft $draft, int $variantId): void
    {
        $variant = PostVariant::query()->findOrFail($variantId);
        $this->state->selectVariant($draft, $variant);
        $this->bot->sendApprovalRequest($draft->refresh());
    }

    private function approveAndPublish(PostDraft $draft): void
    {
        $this->state->approve($draft);
        PublishTelegramPostJob::dispatch($draft->id);
        $this->bot->sendMessage($draft->user->telegramAccount?->telegram_user_id, 'Approved. Publishing started...');
    }

    private function regenerate(PostDraft $draft): void
    {
        $this->state->regenerate($draft);
        GeneratePostVariantsJob::dispatch($draft->id);
        $this->bot->sendMessage($draft->user->telegramAccount?->telegram_user_id, 'Regenerating 3 post variants...');
    }

    private function cancel(PostDraft $draft): void
    {
        $this->state->cancel($draft);
        $this->bot->sendMessage($draft->user->telegramAccount?->telegram_user_id, 'Draft cancelled.');
    }

    private function accountFor(array $from): TelegramAccount
    {
        $telegramUserId = (string) $from['id'];
        $account = TelegramAccount::query()->where('telegram_user_id', $telegramUserId)->first();

        if (! $account) {
            $user = User::query()->create([
                'name' => $this->displayName($from),
            ]);

            return TelegramAccount::query()->create([
                'user_id' => $user->id,
                'telegram_user_id' => $telegramUserId,
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
            ]);
        }

        $account->update([
            'username' => $from['username'] ?? $account->username,
            'first_name' => $from['first_name'] ?? $account->first_name,
            'last_name' => $from['last_name'] ?? $account->last_name,
        ]);

        return $account->refresh();
    }

    private function displayName(array $from): string
    {
        $name = trim((string) (($from['first_name'] ?? '').' '.($from['last_name'] ?? '')));

        return $name !== '' ? $name : ($from['username'] ?? 'Telegram User');
    }

    private function isCommand(string $text, string $command): bool
    {
        return Str::startsWith($text, [$command, $command.'@']);
    }
}
