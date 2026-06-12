<?php

namespace App\Enums;

enum HashtagStyle: string
{
    case None = 'none';
    case Minimal = 'minimal';
    case Normal = 'normal';
    case Aggressive = 'aggressive';
}
