<?php

namespace App\Domain\People\Actions;

use App\Domain\Academic\Models\Classroom;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\RollNumbers;
use Illuminate\Support\Facades\DB;

class EnrollStudents
{
    /**
     * Puts students into a class for the class's year. The unique (student, year) key refuses a second class.
     *
     * @param  list<int>  $studentIds
     */
    public function handle(Classroom $classroom, array $studentIds): void
    {
        DB::transaction(function () use ($classroom, $studentIds) {
            foreach ($studentIds as $studentId) {
                Enrollment::create([
                    'academic_year_id' => $classroom->academic_year_id,
                    'classroom_id' => $classroom->id,
                    'student_id' => $studentId,
                    'joined_at' => now()->toDateString(),
                ]);
            }

            RollNumbers::renumber($classroom->id);

            activity()->performedOn($classroom)->causedBy(auth()->user())
                ->withProperties(['student_ids' => $studentIds])
                ->log('enrollment.added');
        });
    }
}
