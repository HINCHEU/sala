<?php

namespace App\Domain\People\Actions;

use App\Domain\Academic\Models\Classroom;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\RollNumbers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MoveStudents
{
    /**
     * Moves enrollments to another class of the same year (the request checks the year).
     * The enrollment row is kept, so anything already recorded on it moves with the student.
     *
     * @param  Collection<int, Enrollment>  $enrollments
     */
    public function handle(Classroom $target, Collection $enrollments): void
    {
        DB::transaction(function () use ($target, $enrollments) {
            $from = $enrollments->pluck('classroom_id')->all();

            Enrollment::whereKey($enrollments->modelKeys())->update(['classroom_id' => $target->id]);
            RollNumbers::renumber($target->id, ...$from);

            activity()->performedOn($target)->causedBy(auth()->user())
                ->withProperties(['enrollment_ids' => $enrollments->modelKeys(), 'from_classroom_ids' => array_values(array_unique($from))])
                ->log('enrollment.moved');
        });
    }
}
