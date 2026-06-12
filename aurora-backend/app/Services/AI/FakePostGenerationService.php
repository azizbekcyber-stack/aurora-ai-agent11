<?php

namespace App\Services\AI;

use App\Contracts\PostGenerator;
use App\DTO\GeneratedPostVariantsResult;
use App\Models\PostDraft;

class FakePostGenerationService implements PostGenerator
{
    public function generate(PostDraft $draft): GeneratedPostVariantsResult
    {
        $draft->loadMissing('user.brandProfile');

        $brand = $draft->user->brandProfile;
        $requestPayload = [
            'prompt' => $draft->prompt,
            'image_path' => $draft->image_path,
            'brand_profile' => $brand?->only([
                'default_language',
                'tone',
                'audience',
                'emoji_level',
                'hashtag_style',
                'banned_words',
            ]),
            'variant_count' => 3,
        ];

        $base = trim($draft->prompt);
        $variants = [
            [
                'title' => 'Clear announcement',
                'body' => "{$base}\n\nHere is a polished Telegram-ready version focused on clarity.",
                'hashtags' => ['aurora'],
                'cta' => 'Tell us what you think.',
                'telegram_text' => "{$base}\n\nHere is a polished Telegram-ready version focused on clarity.\n\nTell us what you think.\n#aurora",
                'risk_flags' => [],
            ],
            [
                'title' => 'Warm community note',
                'body' => "{$base}\n\nA warmer version that feels direct and conversational.",
                'hashtags' => ['community'],
                'cta' => 'Reply with your questions.',
                'telegram_text' => "{$base}\n\nA warmer version that feels direct and conversational.\n\nReply with your questions.\n#community",
                'risk_flags' => [],
            ],
            [
                'title' => 'Compact promo',
                'body' => "{$base}\n\nA compact version with a stronger opening and clean ending.",
                'hashtags' => ['update'],
                'cta' => 'Save this post for later.',
                'telegram_text' => "{$base}\n\nA compact version with a stronger opening and clean ending.\n\nSave this post for later.\n#update",
                'risk_flags' => [],
            ],
        ];

        return new GeneratedPostVariantsResult(
            provider: 'fake',
            model: 'fake-aurora-local',
            variants: $variants,
            requestPayload: $requestPayload,
            responsePayload: ['variants' => $variants],
        );
    }
}
