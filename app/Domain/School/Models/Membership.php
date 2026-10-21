<?php

namespace App\Domain\School\Models;

use App\Domain\School\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A user's membership of a school (school_user). Roles live in spatie's tables, scoped by school.
 *
 * @property MembershipStatus $status
 */
class Membership extends Pivot
{
    protected $table = 'school_user';

    public $incrementing = true;

    protected function casts(): array
    {
        return ['status' => MembershipStatus::class];
    }
}
