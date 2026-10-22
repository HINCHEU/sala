<?php

namespace App\Domain\People\Enums;

/** Students are never deleted: they leave through a status, so history and statistics stay correct. */
enum StudentStatus: string
{
    case Active = 'active';
    case Transferred = 'transferred';
    case Dropped = 'dropped';
    case Graduated = 'graduated';
}
