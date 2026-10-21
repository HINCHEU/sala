<?php

namespace App\Domain\School\Enums;

/**
 * Permission names checked with Gate / `can:` middleware. Later milestones add theirs here
 * and to Role::permissions(); the seeder syncs both.
 */
enum Permission: string
{
    case ManageSettings = 'school.manage-settings';
    case ManageMembers = 'school.manage-members';
}
