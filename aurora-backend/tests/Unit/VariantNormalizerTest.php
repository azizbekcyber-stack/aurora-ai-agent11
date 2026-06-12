<?php

namespace Tests\Unit;

use App\Exceptions\InvalidAiResponseException;
use App\Services\AI\VariantNormalizer;
use PHPUnit\Framework\TestCase;

class VariantNormalizerTest extends TestCase
{
    public function test_valid_structured_ai_response_is_parsed(): void
    {
        $variants = $this->validVariants();

        $normalized = (new VariantNormalizer())->normalize(['variants' => $variants]);

        $this->assertCount(3, $normalized);
        $this->assertSame('Text 1', $normalized[0]['telegram_text']);
    }

    public function test_more_or_fewer_than_three_variants_are_rejected(): void
    {
        $this->expectException(InvalidAiResponseException::class);

        (new VariantNormalizer())->normalize(['variants' => array_slice($this->validVariants(), 0, 2)]);
    }

    public function test_missing_variant_fields_are_rejected(): void
    {
        $variants = $this->validVariants();
        unset($variants[0]['telegram_text']);

        $this->expectException(InvalidAiResponseException::class);

        (new VariantNormalizer())->normalize(['variants' => $variants]);
    }

    public function test_invalid_ai_response_shape_is_rejected(): void
    {
        $this->expectException(InvalidAiResponseException::class);

        (new VariantNormalizer())->normalize(['variants' => ['not-an-object']]);
    }

    private function validVariants(): array
    {
        return [
            [
                'title' => 'One',
                'body' => 'Body 1',
                'hashtags' => [],
                'cta' => null,
                'telegram_text' => 'Text 1',
                'risk_flags' => [],
            ],
            [
                'title' => 'Two',
                'body' => 'Body 2',
                'hashtags' => ['tag'],
                'cta' => 'Read more',
                'telegram_text' => 'Text 2',
                'risk_flags' => [],
            ],
            [
                'title' => 'Three',
                'body' => 'Body 3',
                'hashtags' => [],
                'cta' => null,
                'telegram_text' => 'Text 3',
                'risk_flags' => [],
            ],
        ];
    }
}
