<?php

namespace App\Models;

use App\Enums\ChannelStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramChannel extends Model
{
    protected $fillable = [
        'user_id',
        'chat_id',
        'username',
        'title',
        'bot_can_post_messages',
        'status',
        'connected_at',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'bot_can_post_messages' => 'boolean',
            'status' => ChannelStatus::class,
            'connected_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(PostDraft::class);
    }
}
