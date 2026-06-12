<?php

namespace App\DTO;

use App\Enums\PublishStrategy;

class PublishResult
{
    /**
     * @param  array<int, string|int>  $messageIds
     */
    public function __construct(
        public readonly bool $success,
        public readonly PublishStrategy $strategy,
        public readonly array $messageIds = [],
        public readonly ?string $errorMessage = null,
    ) {
    }

    public static function success(PublishStrategy $strategy, array $messageIds): self
    {
        return new self(true, $strategy, $messageIds);
    }

    public static function failed(PublishStrategy $strategy, string $errorMessage): self
    {
        return new self(false, $strategy, [], $errorMessage);
    }
}
