<?php

namespace App\Domain\School\Enums;

enum SchoolStage: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case K12 = 'k12';
}
