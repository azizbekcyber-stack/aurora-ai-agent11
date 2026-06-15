<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\TelegramLoginService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAuroraUser
{
    public function __construct(private readonly TelegramLoginService $logins)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->resolveUser($request);

        if (! $user) {
            return response()->json([
                'message' => 'Please connect Telegram to access Aurora.',
            ], 401);
        }

        $request->attributes->set('aurora_user', $user);

        return $next($request);
    }

    private function resolveUser(Request $request): ?User
    {
        if ($user = $this->logins->findUserBySessionToken((string) $request->bearerToken())) {
            return $user;
        }

        $dashboardToken = (string) config('aurora.dashboard_token');
        $providedDashboardToken = (string) $request->header('X-Aurora-Dashboard-Token');
        $userId = $request->header('X-Aurora-User-Id') ?: $request->query('user_id');

        if ($dashboardToken !== '' && hash_equals($dashboardToken, $providedDashboardToken) && $userId) {
            return User::query()->find($userId);
        }

        if ($dashboardToken === '' && $userId) {
            return User::query()->find($userId);
        }

        if ($dashboardToken === '') {
            return User::query()->firstOrCreate(
                ['email' => 'demo@aurora.local'],
                ['name' => 'Aurora Demo', 'password' => null],
            );
        }

        return null;
    }
}
