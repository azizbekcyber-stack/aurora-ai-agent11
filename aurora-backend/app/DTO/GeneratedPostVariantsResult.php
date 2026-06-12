<?php

namespace App\DTO;

class GeneratedPostVariantsResult
{
    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @param  array<string, mixed>|null  $requestPayload
     * @param  array<string, mixed>|null  $responsePayload
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly array $variants,
        public readonly ?array $requestPayload = null,
        public readonly ?array $responsePayload = null,
    ) {
    }
}
