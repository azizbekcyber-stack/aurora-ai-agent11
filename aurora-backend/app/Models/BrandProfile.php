<?php

namespace App\Models;

use App\Enums\BrandLanguage;
use App\Enums\EmojiLevel;
use App\Enums\HashtagStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandProfile extends Model
{
    protected $fillable = [
        'user_id',
        'default_language',
        'tone',
        'audience',
        'emoji_level',
        'hashtag_style',
        'banned_words',
    ];

    protected function casts(): array
    {
        return [
            'default_language' => BrandLanguage::class,
            'emoji_level' => EmojiLevel::class,
            'hashtag_style' => HashtagStyle::class,
            'banned_words' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
