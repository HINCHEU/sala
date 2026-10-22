<?php

namespace App\Domain\People\Enums;

enum TeacherStatus: string
{
    case Active = 'active';
    case Left = 'left';
}
