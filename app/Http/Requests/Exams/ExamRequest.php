<?php

namespace App\Http\Requests\Exams;

use App\Domain\Exams\Enums\ExamType;
use App\Http\Requests\SchoolRequest;
use Illuminate\Validation\Rule;

class ExamRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', $this->existsInSchool('academic_years')],
            'term_id' => ['nullable', $this->existsInSchool('terms')],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(ExamType::class)],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'classroom_ids' => ['required', 'array', 'min:1'],
            'classroom_ids.*' => ['integer', $this->existsInSchool('classrooms')],
        ];
    }
}
