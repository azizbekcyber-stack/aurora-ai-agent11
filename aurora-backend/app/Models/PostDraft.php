<?php

namespace App\Models;

use App\Enums\DraftSource;
use App\Enums\DraftStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostDraft extends Model
{
    protected $fillable = [
        'user_id',
        'telegram_channel_id',
        'prompt',
        'image_path',
        'source',
        'status',
        'selected_variant_id',
    ];

    protected function casts(): array
    {
        return [
            'source' => DraftSource::class,
            'status' => DraftStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function telegramChannel(): BelongsTo
    {
        return $this->belongsTo(TelegramChannel::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(PostVariant::class);
    }

    public function selectedVariant(): BelongsTo
    {
        return $this->belongsTo(PostVariant::class, 'selected_variant_id');
    }

    public function aiGenerationLogs(): HasMany
    {
        return $this->hasMany(AiGenerationLog::class);
    }

    public function publishLogs(): HasMany
    {
        return $this->hasMany(PublishLog::class);
    }
}
