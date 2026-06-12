<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostVariant extends Model
{
    protected $fillable = [
        'post_draft_id',
        'title',
        'body',
        'hashtags',
        'cta',
        'telegram_text',
        'risk_flags',
    ];

    protected function casts(): array
    {
        return [
            'hashtags' => 'array',
            'risk_flags' => 'array',
        ];
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(PostDraft::class, 'post_draft_id');
    }
}
