<?php

namespace App\Models;

use App\Enums\AiGenerationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGenerationLog extends Model
{
    protected $fillable = [
        'post_draft_id',
        'provider',
        'model',
        'request_payload',
        'response_payload',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'status' => AiGenerationStatus::class,
        ];
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(PostDraft::class, 'post_draft_id');
    }
}
