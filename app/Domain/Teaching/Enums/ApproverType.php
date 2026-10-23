<?php

namespace App\Domain\Teaching\Enums;

enum ApproverType: string
{
    case Homeroom = 'homeroom';
    case Role = 'role';
    case User = 'user';

    public function label(): string
    {
        return __('academic.approver_types.'.$this->value);
    }
}
