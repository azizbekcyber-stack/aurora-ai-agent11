<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramAccount extends Model
{
    protected $fillable = [
        'user_id',
        'telegram_user_id',
        'username',
        'first_name',
        'last_name',
        'pending_action',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
