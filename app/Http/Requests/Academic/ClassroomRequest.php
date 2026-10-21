<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Enums\Shift;
use App\Domain\Academic\Models\GradeLevel;
use App\Http\Requests\SchoolRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ClassroomRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', $this->existsInSchool('academic_years')],
            'grade_level_id' => ['required', $this->existsInSchool('grade_levels')],
            'track_id' => ['nullable', $this->existsInSchool('tracks')],
            'room_id' => ['nullable', $this->existsInSchool('rooms')],
            'name' => [
                'required', 'string', 'max:20',
                $this->uniqueInSchool('classrooms', 'name', $this->route('classroom'))
                    ->where('academic_year_id', $this->integer('academic_year_id')),
            ],
            'shift' => ['required', Rule::enum(Shift::class)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator) {
            $grade = GradeLevel::find($this->input('grade_level_id'));
            if ($grade && $grade->has_tracks !== $this->filled('track_id')) {
                $validator->errors()->add('track_id', __($grade->has_tracks ? 'academic.track_required' : 'academic.track_not_used'));
            }
        }];
    }
}
