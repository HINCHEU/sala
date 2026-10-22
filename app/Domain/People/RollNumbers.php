<?php

namespace App\Domain\People;

use App\Domain\People\Models\Enrollment;

/** Roll numbers follow Khmer alphabetical order, so they are recomputed whenever a class's list changes. */
final class RollNumbers
{
    public static function renumber(int ...$classroomIds): void
    {
        foreach (array_unique($classroomIds) as $classroomId) {
            $ids = Enrollment::where('enrollments.classroom_id', $classroomId)
                ->join('students', 'students.id', '=', 'enrollments.student_id')
                ->orderBy('students.last_name_km')->orderBy('students.first_name_km')->orderBy('students.id')
                ->pluck('enrollments.id');

            foreach ($ids as $i => $id) {
                Enrollment::whereKey($id)->update(['roll_no' => $i + 1]);
            }
        }
    }
}
