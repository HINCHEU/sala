<?php

namespace App\Http\Requests\People;

use App\Domain\Academic\Models\Classroom;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Student;
use App\Http\Requests\SchoolRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Validator;

/**
 * The enrollment board's three changes. Adding takes student_ids and a classroom; moving takes
 * enrollment_ids and a classroom of the same year; removing takes enrollment_ids.
 */
class EnrollmentRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $adding = $this->isMethod('post');

        return [
            'classroom_id' => [$this->isMethod('delete') ? 'nullable' : 'required', 'integer', $this->existsInSchool('classrooms')],
            'student_ids' => [$adding ? 'required' : 'prohibited', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'distinct', $this->existsInSchool('students')],
            'enrollment_ids' => [$adding ? 'prohibited' : 'required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer', 'distinct', $this->existsInSchool('enrollments')],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $classroom = $this->classroom();

            if ($this->isMethod('post')) {
                $taken = Enrollment::where('academic_year_id', $classroom?->academic_year_id)
                    ->whereIn('student_id', $this->validated('student_ids'))->pluck('student_id');
                if ($taken->isNotEmpty()) {
                    $names = Student::whereKey($taken)->get()->map->nameKm()->join(', ');
                    $validator->errors()->add('student_ids', __('people.already_enrolled', ['names' => $names]));
                }
            } elseif ($this->isMethod('put') && $this->enrollments()->contains(fn (Enrollment $e) => (int) $e->academic_year_id !== (int) $classroom?->academic_year_id)) {
                $validator->errors()->add('classroom_id', __('people.other_year'));
            }
        }];
    }

    public function classroom(): ?Classroom
    {
        return $this->filled('classroom_id') ? Classroom::find($this->integer('classroom_id')) : null;
    }

    /** @return list<int> */
    public function studentIds(): array
    {
        return array_map('intval', $this->validated('student_ids'));
    }

    /** @return Collection<int, Enrollment> */
    public function enrollments(): Collection
    {
        return Enrollment::whereKey($this->input('enrollment_ids', []))->get();
    }
}
