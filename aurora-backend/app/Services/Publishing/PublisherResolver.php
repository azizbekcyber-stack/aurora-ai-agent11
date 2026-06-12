<?php

namespace App\Services\Publishing;

use App\Contracts\SocialPublisher;
use App\Enums\PublishPlatform;
use App\Services\Telegram\TelegramPublisher;

class PublisherResolver
{
    public function __construct(private readonly TelegramPublisher $telegramPublisher)
    {
    }

    public function resolve(PublishPlatform $platform): SocialPublisher
    {
        return match ($platform) {
            PublishPlatform::Telegram => $this->telegramPublisher,
        };
    }
}
