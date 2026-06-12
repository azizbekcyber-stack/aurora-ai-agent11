<?php

namespace App\Models;

use App\Enums\PublishLogStatus;
use App\Enums\PublishPlatform;
use App\Enums\PublishStrategy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishLog extends Model
{
    protected $fillable = [
        'post_draft_id',
        'platform',
        'status',
        'telegram_message_ids',
        'publish_strategy',
        'error_message',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => PublishPlatform::class,
            'status' => PublishLogStatus::class,
            'telegram_message_ids' => 'array',
            'publish_strategy' => PublishStrategy::class,
            'published_at' => 'datetime',
        ];
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(PostDraft::class, 'post_draft_id');
    }
}
