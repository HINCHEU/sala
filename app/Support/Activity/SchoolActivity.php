<?php

namespace App\Support\Activity;

use App\Domain\School\Models\School;
use App\Support\Tenancy\CurrentSchool;
use Spatie\Activitylog\Models\Activity;

/**
 * A logged event, stamped with its school. Not scoped automatically: the platform also logs events with
 * no current school, so whoever reads this for a school filters by school_id.
 *
 * @property ?int $school_id
 */
class SchoolActivity extends Activity
{
    protected static function booted(): void
    {
        static::creating(function (self $activity) {
            $activity->school_id ??= app(CurrentSchool::class)->id()
                ?? ($activity->subject_type === (new School)->getMorphClass() ? (int) $activity->subject_id : null);
        });
    }
}
