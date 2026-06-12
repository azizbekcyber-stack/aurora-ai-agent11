<?php

namespace App\Enums;

enum ChannelStatus: string
{
    case Pending = 'pending';
    case Connected = 'connected';
    case Failed = 'failed';
    case Disconnected = 'disconnected';
}
