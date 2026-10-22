<?php

namespace App\Domain\People\Actions;

use App\Domain\People\Models\Teacher;
use Illuminate\Support\Facades\DB;

class SaveTeacher
{
    /**
     * Creates the teacher when $teacher is null, and sets the subjects they can teach.
     *
     * @param  array<string, mixed>  $data
     * @param  list<int>  $subjectIds
     */
    public function handle(?Teacher $teacher, array $data, array $subjectIds): Teacher
    {
        return DB::transaction(function () use ($teacher, $data, $subjectIds) {
            $creating = $teacher === null;
            $teacher ??= new Teacher;
            $teacher->fill($data)->save();

            $teacher->subjects()->sync(array_fill_keys($subjectIds, ['school_id' => $teacher->school_id]));

            activity()->performedOn($teacher)->causedBy(auth()->user())->log($creating ? 'teacher.created' : 'teacher.updated');

            return $teacher;
        });
    }
}
