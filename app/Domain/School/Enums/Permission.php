<?php

namespace App\Domain\School\Enums;

/**
 * Permission names checked with Gate / `can:` middleware. Later milestones add theirs here
 * and to Role::permissions(); `php artisan sala:sync-roles` (run on deploy) gives existing schools the change.
 */
enum Permission: string
{
    case ManageSettings = 'school.manage-settings';
    case ManageMembers = 'school.manage-members';
    case ManageAcademic = 'academic.manage';
}
