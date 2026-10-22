<?php

namespace App\Domain\People\Enums;

enum ImportStatus: string
{
    case Previewed = 'previewed';
    case Queued = 'queued';
    case Done = 'done';
    case Failed = 'failed';
}
