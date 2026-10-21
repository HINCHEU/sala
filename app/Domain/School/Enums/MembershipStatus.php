<?php

namespace App\Domain\School\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
