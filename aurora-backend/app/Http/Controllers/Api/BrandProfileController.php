<?php

namespace App\Http\Controllers\Api;

use App\Enums\BrandLanguage;
use App\Enums\EmojiLevel;
use App\Enums\HashtagStyle;
use App\Http\Controllers\Controller;
use App\Models\BrandProfile;
use App\Services\Auth\CurrentUserResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandProfileController extends Controller
{
    public function __construct(private readonly CurrentUserResolver $users)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $this->users->resolve($request);

        return response()->json($this->profileFor($user->id));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $this->users->resolve($request);

        $validated = $request->validate([
            'default_language' => ['required', Rule::enum(BrandLanguage::class)],
            'tone' => ['nullable', 'string', 'max:255'],
            'audience' => ['nullable', 'string', 'max:255'],
            'emoji_level' => ['required', Rule::enum(EmojiLevel::class)],
            'hashtag_style' => ['required', Rule::enum(HashtagStyle::class)],
            'banned_words' => ['nullable', 'array'],
            'banned_words.*' => ['string', 'max:100'],
        ]);

        $profile = $this->profileFor($user->id);
        $profile->update($validated);

        return response()->json($profile->refresh());
    }

    private function profileFor(int $userId): BrandProfile
    {
        return BrandProfile::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'default_language' => BrandLanguage::English,
                'emoji_level' => EmojiLevel::Medium,
                'hashtag_style' => HashtagStyle::Normal,
            ],
        );
    }
}
