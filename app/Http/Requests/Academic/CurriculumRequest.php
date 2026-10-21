<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Models\GradeLevel;
use App\Http\Requests\SchoolRequest;
use Illuminate\Validation\Validator;

class CurriculumRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', $this->existsInSchool('academic_years')],
            'grade_level_id' => ['required', $this->existsInSchool('grade_levels')],
            'track_id' => ['nullable', $this->existsInSchool('tracks')],
            'divisor' => ['nullable', 'numeric', 'gt:0', 'max:9999.99'],
            'rows' => ['present', 'array'],
            'rows.*.subject_id' => ['required', 'distinct', $this->existsInSchool('subjects')],
            'rows.*.max_score' => ['required', 'numeric', 'gt:0', 'max:999.99'],
            'rows.*.coefficient' => ['required', 'numeric', 'gt:0', 'max:99.99'],
            'rows.*.pass_score' => ['required', 'numeric', 'min:0', 'lte:rows.*.max_score'],
            'rows.*.sort' => ['required', 'integer', 'min:0', 'max:999'],
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

    /** @return list<array{subject_id: int, max_score: float, coefficient: float, pass_score: float, sort: int}> */
    public function rows(): array
    {
        return array_map(fn (array $row) => [
            'subject_id' => (int) $row['subject_id'],
            'max_score' => (float) $row['max_score'],
            'coefficient' => (float) $row['coefficient'],
            'pass_score' => (float) $row['pass_score'],
            'sort' => (int) $row['sort'],
        ], array_values($this->validated('rows')));
    }
}
