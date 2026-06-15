<?php

namespace App\Services\Auth;

use App\Models\TelegramLoginToken;
use App\Models\User;
use Illuminate\Support\Str;

class TelegramLoginService
{
    public function createChallenge(): TelegramLoginToken
    {
        TelegramLoginToken::query()
            ->whereNull('user_id')
            ->where('expires_at', '<', now())
            ->delete();

        return TelegramLoginToken::query()->create([
            'code' => 'login_'.Str::random(32),
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function claim(string $code, User $user): ?TelegramLoginToken
    {
        $challenge = TelegramLoginToken::query()
            ->where('code', $code)
            ->whereNull('user_id')
            ->where('expires_at', '>', now())
            ->first();

        if (! $challenge) {
            return null;
        }

        $challenge->forceFill([
            'user_id' => $user->id,
            'session_token' => hash('sha256', Str::random(80)),
            'claimed_at' => now(),
        ])->save();

        return $challenge->refresh();
    }

    public function consume(string $code): ?TelegramLoginToken
    {
        return TelegramLoginToken::query()
            ->with('user.telegramAccount', 'user.telegramChannel')
            ->where('code', $code)
            ->whereNotNull('user_id')
            ->whereNotNull('session_token')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function findUserBySessionToken(string $token): ?User
    {
        if ($token === '') {
            return null;
        }

        $login = TelegramLoginToken::query()
            ->where('session_token', $token)
            ->where('expires_at', '>', now()->subDays(30))
            ->latest('claimed_at')
            ->first();

        return $login?->user;
    }
}
