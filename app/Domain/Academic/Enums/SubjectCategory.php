<?php

namespace App\Domain\Academic\Enums;

enum SubjectCategory: string
{
    case Language = 'language';
    case Science = 'science';
    case Social = 'social';
    case Other = 'other';
}
