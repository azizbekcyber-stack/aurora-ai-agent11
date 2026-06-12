<?php

namespace App\Services\Telegram;

use InvalidArgumentException;
use Illuminate\Support\Facades\Storage;

class TelegramFileService
{
    public function __construct(private readonly TelegramBotService $bot)
    {
    }

    public function downloadLargestPhoto(array $message, int|string $userId): ?string
    {
        $photos = $message['photo'] ?? [];

        if ($photos === []) {
            return null;
        }

        usort($photos, fn (array $a, array $b): int => ($b['file_size'] ?? 0) <=> ($a['file_size'] ?? 0));

        $photo = $photos[0];
        $maxBytes = (int) config('aurora.max_image_bytes', 10 * 1024 * 1024);

        if (($photo['file_size'] ?? 0) > $maxBytes) {
            throw new InvalidArgumentException('Image is too large.');
        }

        $file = $this->bot->getFile($photo['file_id']);
        $filePath = $file['file_path'] ?? null;

        if (! is_string($filePath)) {
            throw new InvalidArgumentException('Telegram did not return a downloadable file path.');
        }

        $contents = $this->bot->downloadFile($filePath);

        if (strlen($contents) > $maxBytes) {
            throw new InvalidArgumentException('Image is too large.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: '';

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new InvalidArgumentException('Unsupported image type.');
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $path = sprintf('telegram/%s/%s.%s', $userId, uniqid('photo_', true), $extension);
        Storage::disk('local')->put($path, $contents);

        return $path;
    }
}
