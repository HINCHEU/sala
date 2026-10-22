<?php

namespace App\Domain\People\Actions;

use App\Domain\People\Models\Enrollment;
use App\Domain\People\RollNumbers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UnenrollStudents
{
    /**
     * Takes students out of their class for a mistaken enrollment. Once scores or attendance hang on an
     * enrollment (M5, M9) their foreign keys refuse the delete; leaving mid-year is a transfer (M12), not this.
     *
     * @param  Collection<int, Enrollment>  $enrollments
     */
    public function handle(Collection $enrollments): void
    {
        DB::transaction(function () use ($enrollments) {
            $classrooms = $enrollments->pluck('classroom_id')->all();

            Enrollment::whereKey($enrollments->modelKeys())->delete();
            RollNumbers::renumber(...$classrooms);

            activity()->causedBy(auth()->user())
                ->withProperties(['student_ids' => $enrollments->pluck('student_id')->all(), 'classroom_ids' => array_values(array_unique($classrooms))])
                ->log('enrollment.removed');
        });
    }
}
