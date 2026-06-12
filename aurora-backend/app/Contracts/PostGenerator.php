<?php

namespace App\Contracts;

use App\DTO\GeneratedPostVariantsResult;
use App\Models\PostDraft;

interface PostGenerator
{
    public function generate(PostDraft $draft): GeneratedPostVariantsResult;
}
