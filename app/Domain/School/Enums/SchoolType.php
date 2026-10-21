<?php

namespace App\Domain\School\Enums;

enum SchoolType: string
{
    case Public = 'public';
    case Private = 'private';
    case International = 'international';
}
