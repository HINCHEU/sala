<?php

namespace App\Domain\People\Enums;

enum Relationship: string
{
    case Father = 'father';
    case Mother = 'mother';
    case Guardian = 'guardian';
}
