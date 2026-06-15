<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\TelegramLoginService;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly TelegramLoginService $logins,
        private readonly TelegramBotService $bot,
    ) {
    }

    public function start(): JsonResponse
    {
        $challenge = $this->logins->createChallenge();
        $bot = $this->bot->getMe();
        $username = (string) ($bot['username'] ?? '');

        return response()->json([
            'code' => $challenge->code,
            'expires_at' => $challenge->expires_at?->toIso8601String(),
            'telegram_url' => $username !== ''
                ? "https://t.me/{$username}?start={$challenge->code}"
                : null,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $login = $this->logins->consume($validated['code']);

        if (! $login || ! $login->user) {
            return response()->json(['authenticated' => false]);
        }

        return response()->json([
            'authenticated' => true,
            'session_token' => $login->session_token,
            'user' => [
                'id' => $login->user->id,
                'name' => $login->user->name,
                'telegram' => $login->user->telegramAccount,
                'channel' => $login->user->telegramChannel,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('aurora_user');

        return response()->json([
            'id' => $user?->id,
            'name' => $user?->name,
            'telegram' => $user?->telegramAccount,
            'channel' => $user?->telegramChannel,
        ]);
    }
}
