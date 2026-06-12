<?php

namespace App\Enums;

enum DraftStatus: string
{
    case Draft = 'draft';
    case Generating = 'generating';
    case Generated = 'generated';
    case Selected = 'selected';
    case Approved = 'approved';
    case Publishing = 'publishing';
    case Published = 'published';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
