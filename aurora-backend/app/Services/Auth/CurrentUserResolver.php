<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CurrentUserResolver
{
    public function resolve(Request $request): User
    {
        $userId = $request->header('X-Aurora-User-Id') ?: $request->query('user_id');

        if ($userId) {
            return User::query()->findOrFail($userId);
        }

        return User::query()->firstOrCreate(
            ['email' => 'demo@aurora.local'],
            [
                'name' => 'Aurora Demo',
                'password' => Str::password(),
            ],
        );
    }
}
