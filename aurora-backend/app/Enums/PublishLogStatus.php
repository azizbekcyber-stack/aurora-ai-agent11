<?php

namespace App\Enums;

enum PublishLogStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
}
