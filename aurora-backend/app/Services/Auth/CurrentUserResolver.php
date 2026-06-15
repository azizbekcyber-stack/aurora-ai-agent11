<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;

class CurrentUserResolver
{
    public function resolve(Request $request): User
    {
        $user = $request->attributes->get('aurora_user');

        if ($user instanceof User) {
            return $user;
        }

        abort(401, 'Please connect Telegram to access Aurora.');
    }
}
