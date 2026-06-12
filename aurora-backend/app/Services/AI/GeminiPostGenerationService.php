<?php

namespace App\Services\AI;

use App\Contracts\PostGenerator;
use App\DTO\GeneratedPostVariantsResult;
use App\Exceptions\InvalidAiResponseException;
use App\Models\PostDraft;
use Illuminate\Support\Facades\Http;

class GeminiPostGenerationService implements PostGenerator
{
    public function __construct(
        private readonly VariantNormalizer $normalizer,
        private readonly ImageContextService $imageContext,
    ) {
    }

    public function generate(PostDraft $draft): GeneratedPostVariantsResult
    {
        $draft->loadMissing('user.brandProfile');

        $model = $draft->image_path
            ? config('services.gemini.model_vision', 'gemini-2.5-flash')
            : config('services.gemini.model_text', 'gemini-2.5-flash');

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $this->systemPrompt()],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $this->buildParts($draft),
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->responseSchema(),
            ],
        ];

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(90)
            ->post($this->endpoint($model), $payload);

        if ($response->failed()) {
            throw new InvalidAiResponseException('Gemini generation request failed.');
        }

        $responsePayload = $response->json();
        $decoded = json_decode($this->extractText($responsePayload), true);

        if (! is_array($decoded)) {
            throw new InvalidAiResponseException('Gemini response was not valid JSON.');
        }

        return new GeneratedPostVariantsResult(
            provider: 'gemini',
            model: $model,
            variants: $this->normalizer->normalize($decoded),
            requestPayload: $payload,
            responsePayload: $responsePayload,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildParts(PostDraft $draft): array
    {
        $parts = [
            ['text' => $this->buildUserPrompt($draft)],
        ];

        if ($image = $this->imageContext->toInlineData($draft->image_path)) {
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $image['mime_type'],
                    'data' => $image['base64'],
                ],
            ];
        }

        return $parts;
    }

    private function endpoint(string $model): string
    {
        $key = (string) config('services.gemini.key');

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
    }

    private function buildUserPrompt(PostDraft $draft): string
    {
        $brand = $draft->user->brandProfile;

        return trim(implode("\n\n", array_filter([
            "User prompt:\n{$draft->prompt}",
            $draft->image_path ? 'An image is attached. Use image context only when visually clear.' : null,
            $brand ? "Brand profile:\n".json_encode([
                'default_language' => $brand->default_language->value,
                'tone' => $brand->tone,
                'audience' => $brand->audience,
                'emoji_level' => $brand->emoji_level->value,
                'hashtag_style' => $brand->hashtag_style->value,
                'banned_words' => $brand->banned_words ?? [],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null,
        ])));
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are Aurora, a Telegram post assistant. Generate exactly 3 meaningfully different Telegram post variants.
Use the user's prompt as the source of truth. Do not invent exact prices, guarantees, dates, discounts, phone numbers, links, or claims unless the user explicitly provided them.
If image content is unclear, write generally instead of pretending certainty.
Respect the brand profile when present. Keep Telegram formatting readable. Avoid spammy hashtag stuffing unless requested.
Return structured JSON only.
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['variants'],
            'properties' => [
                'variants' => [
                    'type' => 'array',
                    'minItems' => 3,
                    'maxItems' => 3,
                    'items' => [
                        'type' => 'object',
                        'required' => ['title', 'body', 'hashtags', 'cta', 'telegram_text', 'risk_flags'],
                        'properties' => [
                            'title' => ['type' => 'string', 'nullable' => true],
                            'body' => ['type' => 'string'],
                            'hashtags' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'cta' => ['type' => 'string', 'nullable' => true],
                            'telegram_text' => ['type' => 'string'],
                            'risk_flags' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractText(array $payload): string
    {
        foreach (($payload['candidates'] ?? []) as $candidate) {
            foreach (($candidate['content']['parts'] ?? []) as $part) {
                if (isset($part['text']) && is_string($part['text'])) {
                    return $part['text'];
                }
            }
        }

        throw new InvalidAiResponseException('Gemini response did not include output text.');
    }
}
