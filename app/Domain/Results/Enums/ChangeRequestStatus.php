<?php

namespace App\Domain\Results\Enums;

enum ChangeRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
