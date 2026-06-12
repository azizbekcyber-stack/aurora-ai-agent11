<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Storage;

class ImageContextService
{
    public function toDataUrl(?string $imagePath): ?string
    {
        $image = $this->readImage($imagePath);

        if (! $image) {
            return null;
        }

        return sprintf('data:%s;base64,%s', $image['mime_type'], $image['base64']);
    }

    /**
     * @return array{mime_type: string, base64: string}|null
     */
    public function toInlineData(?string $imagePath): ?array
    {
        return $this->readImage($imagePath);
    }

    /**
     * @return array{mime_type: string, base64: string}|null
     */
    private function readImage(?string $imagePath): ?array
    {
        if (! $imagePath || ! Storage::disk('local')->exists($imagePath)) {
            return null;
        }

        $absolutePath = Storage::disk('local')->path($imagePath);
        $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';
        $contents = Storage::disk('local')->get($imagePath);

        return [
            'mime_type' => $mimeType,
            'base64' => base64_encode($contents),
        ];
    }
}
