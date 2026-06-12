<?php

namespace App\Services\AI;

use App\Exceptions\InvalidAiResponseException;

class VariantNormalizer
{
    /**
     * @param  array<string, mixed>|array<int, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function normalize(array $payload): array
    {
        $variants = $payload['variants'] ?? $payload;

        if (! is_array($variants) || array_is_list($variants) === false) {
            throw new InvalidAiResponseException('AI response must contain a variants array.');
        }

        if (count($variants) !== 3) {
            throw new InvalidAiResponseException('AI response must contain exactly 3 variants.');
        }

        return array_map(function (mixed $variant): array {
            if (! is_array($variant)) {
                throw new InvalidAiResponseException('Each variant must be an object.');
            }

            foreach (['title', 'body', 'hashtags', 'cta', 'telegram_text', 'risk_flags'] as $field) {
                if (! array_key_exists($field, $variant)) {
                    throw new InvalidAiResponseException("Variant is missing {$field}.");
                }
            }

            if (! is_string($variant['body']) || trim($variant['body']) === '') {
                throw new InvalidAiResponseException('Variant body must be a non-empty string.');
            }

            if (! is_string($variant['telegram_text']) || trim($variant['telegram_text']) === '') {
                throw new InvalidAiResponseException('Variant telegram_text must be a non-empty string.');
            }

            return [
                'title' => is_string($variant['title']) ? $variant['title'] : null,
                'body' => $variant['body'],
                'hashtags' => is_array($variant['hashtags']) ? array_values($variant['hashtags']) : [],
                'cta' => is_string($variant['cta']) && trim($variant['cta']) !== '' ? $variant['cta'] : null,
                'telegram_text' => $variant['telegram_text'],
                'risk_flags' => is_array($variant['risk_flags']) ? array_values($variant['risk_flags']) : [],
            ];
        }, $variants);
    }
}
