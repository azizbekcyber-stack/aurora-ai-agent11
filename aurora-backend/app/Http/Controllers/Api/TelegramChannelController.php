<?php

namespace App\Http\Controllers\Api;

use App\Enums\ChannelStatus;
use App\Http\Controllers\Controller;
use App\Services\Auth\CurrentUserResolver;
use App\Services\Telegram\TelegramChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TelegramChannelController extends Controller
{
    public function __construct(
        private readonly CurrentUserResolver $users,
        private readonly TelegramChannelService $channels,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $this->users->resolve($request);

        return response()->json($user->telegramChannel);
    }

    public function connect(Request $request): JsonResponse
    {
        $user = $this->users->resolve($request);
        $validated = $request->validate([
            'channel' => ['required', 'string', 'max:255'],
        ]);

        try {
            $channel = $this->channels->connectFromIdentifier($user, $validated['channel']);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'The bot does not have permission to post in this channel. Please add the bot as an admin and enable post permission.',
                'error' => $exception->getMessage(),
            ], 422);
        }

        $status = $channel->status === ChannelStatus::Connected ? 200 : 422;

        return response()->json($channel, $status);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $user = $this->users->resolve($request);
        $channel = $user->telegramChannel;

        if ($channel) {
            $channel->forceFill([
                'status' => ChannelStatus::Disconnected,
                'bot_can_post_messages' => false,
            ])->save();
        }

        return response()->json(['message' => 'Telegram channel disconnected.']);
    }
}
