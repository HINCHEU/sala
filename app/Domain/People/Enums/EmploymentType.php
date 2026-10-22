<?php

namespace App\Domain\People\Enums;

enum EmploymentType: string
{
    case CivilServant = 'civil_servant';
    case Contract = 'contract';
    case Volunteer = 'volunteer';
}
