<?php

namespace App\Domain\Results\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Failed = 'failed';
}
