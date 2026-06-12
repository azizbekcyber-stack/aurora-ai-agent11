<?php

namespace App\Services\AI;

use App\Contracts\PostGenerator;
use App\DTO\GeneratedPostVariantsResult;
use App\Exceptions\InvalidAiResponseException;
use App\Models\PostDraft;
use Illuminate\Support\Facades\Http;

class OpenAIPostGenerationService implements PostGenerator
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
            ? config('services.openai.model_vision', 'gpt-5-mini')
            : config('services.openai.model_text', 'gpt-5-mini');

        $content = [
            [
                'type' => 'input_text',
                'text' => $this->buildUserPrompt($draft),
            ],
        ];

        if ($imageUrl = $this->imageContext->toDataUrl($draft->image_path)) {
            $content[] = [
                'type' => 'input_image',
                'image_url' => $imageUrl,
            ];
        }

        $payload = [
            'model' => $model,
            'store' => false,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $this->systemPrompt(),
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'aurora_post_variants',
                    'strict' => true,
                    'schema' => $this->responseSchema(),
                ],
            ],
        ];

        $response = Http::withToken((string) config('services.openai.key'))
            ->acceptJson()
            ->asJson()
            ->timeout(90)
            ->post('https://api.openai.com/v1/responses', $payload);

        if ($response->failed()) {
            throw new InvalidAiResponseException('OpenAI generation request failed.');
        }

        $responsePayload = $response->json();
        $outputText = $this->extractOutputText($responsePayload);
        $decoded = json_decode($outputText, true);

        if (! is_array($decoded)) {
            throw new InvalidAiResponseException('OpenAI response was not valid JSON.');
        }

        return new GeneratedPostVariantsResult(
            provider: 'openai',
            model: $model,
            variants: $this->normalizer->normalize($decoded),
            requestPayload: $payload,
            responsePayload: $responsePayload,
        );
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
            'additionalProperties' => false,
            'required' => ['variants'],
            'properties' => [
                'variants' => [
                    'type' => 'array',
                    'minItems' => 3,
                    'maxItems' => 3,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['title', 'body', 'hashtags', 'cta', 'telegram_text', 'risk_flags'],
                        'properties' => [
                            'title' => ['type' => ['string', 'null']],
                            'body' => ['type' => 'string'],
                            'hashtags' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'cta' => ['type' => ['string', 'null']],
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
    private function extractOutputText(array $payload): string
    {
        if (isset($payload['output_text']) && is_string($payload['output_text'])) {
            return $payload['output_text'];
        }

        foreach (($payload['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                if (isset($content['text']) && is_string($content['text'])) {
                    return $content['text'];
                }
            }
        }

        throw new InvalidAiResponseException('OpenAI response did not include output text.');
    }
}
