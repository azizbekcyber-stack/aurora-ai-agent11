<?php

namespace App\Contracts;

use App\DTO\PublishResult;
use App\Models\PostDraft;

interface SocialPublisher
{
    public function publish(PostDraft $draft): PublishResult;
}
