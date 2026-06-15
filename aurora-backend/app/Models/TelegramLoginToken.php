<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramLoginToken extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'session_token',
        'claimed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
