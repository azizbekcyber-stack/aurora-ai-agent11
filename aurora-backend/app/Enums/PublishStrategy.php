<?php

namespace App\Enums;

enum PublishStrategy: string
{
    case TextOnly = 'text_only';
    case PhotoWithCaption = 'photo_with_caption';
    case PhotoThenText = 'photo_then_text';
    case TextThenPhoto = 'text_then_photo';
}
